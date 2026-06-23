<?php

/**
 * IMedia Registration — SubmitController.
 *
 * Phase 3: real INSERT logic. The HMAC middleware has already verified
 * the request. We:
 *   1. Decode JSON body
 *   2. Validate required fields
 *   3. Look up the form route (target type)
 *   4. Whitelist known fields into typed columns, rest into dynamic_data
 *   5. Dispatch to the right model (Registration / Contact / Application
 *      / CustomSubmission)
 *   6. Log the initial "pending" status to status_history
 *   7. Run the threshold check (no-op for non-confirm inserts)
 *   8. Return 201 { success: true, id: <n> }
 *
 * Per php-pro: strict types, readonly controller, all SQL in models.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Logger, Request, Response};
use App\Services\{StatusLogger, ThresholdChecker};
use App\Models\{Application, Contact, CustomEndpoint, CustomSubmission, FormRoute, Registration, StatusHistory};

final readonly class SubmitController {
    /**
     * Whitelist of fields that map to typed columns in the registrations table.
     * Anything else from the WP plugin's `$clean_data` goes into dynamic_data.
     */
    private const REGISTRATION_FIELDS = array(
        'name',
        'mobile',
        'email',
        'address',
        'course',
        'start_date',
        'end_date',
    );

    /** @var array<int, string> */
    private const CONTACT_FIELDS = array(
        'name',
        'mobile',
        'email',
        'subject',
        'message',
    );

    /** @var array<int, string> */
    private const APPLICATION_FIELDS = array(
        'name',
        'mobile',
        'email',
        'position',
        'message',
    );

    public function handle( Request $req, Response $res ): Response {
        $body = $req->body;

        $formId = (int) ( $body['form_id'] ?? 0 );
        if ($formId <= 0) {
            return $this->error($res, 400, 'invalid_form_id', 'The request is missing a valid form_id.');
        }

        $fields = $body['fields'] ?? null;
        if (! is_array($fields)) {
            return $this->error($res, 400, 'invalid_fields', 'The request is missing a "fields" object.');
        }

        $route = FormRoute::find($formId);
        if ($route === null) {
            Logger::warning(
                'submit.no_route',
                array(
                    'form_id' => $formId,
                    'ip' => $req->ip(),
                )
            );
            return $this->error(
                $res,
                404,
                'no_route',
                "No form route is configured for form_id {$formId}. "
                . 'Open the new app admin > Settings > Form Routes and add one.'
            );
        }

        try {
            $newId = match ($route['target_type']) {
                FormRoute::TARGET_REGISTRATION => $this->insertRegistration($fields),
                FormRoute::TARGET_CONTACT      => $this->insertContact($fields),
                FormRoute::TARGET_OJT          => $this->insertApplication($fields, Application::TYPE_OJT),
                FormRoute::TARGET_TRAINER      => $this->insertApplication($fields, Application::TYPE_TRAINER),
                FormRoute::TARGET_CUSTOM       => $this->insertCustom($fields, (string) ( $route['target_slug'] ?? '' )),
                default                        => null,
            };
        } catch (\InvalidArgumentException $e) {
            return $this->error($res, 422, 'invalid_target', $e->getMessage());
        }

        if ($newId === null) {
            return $this->error($res, 500, 'insert_failed', 'The submission could not be saved.');
        }

        Logger::info(
            'submit.received',
            array(
                'form_id'     => $formId,
                'target_type' => $route['target_type'],
                'target_slug' => $route['target_slug'],
                'new_id'      => $newId,
                'ip'          => $req->ip(),
            )
        );

        // Threshold check is a no-op for non-confirm inserts; for
        // registrations (which default to 'pending') it does nothing.
        // It's wired here so a future WP-plugin change to default new
        // submissions to 'tentative' is also handled.
        if ($route['target_type'] === FormRoute::TARGET_REGISTRATION) {
            try {
                ThresholdChecker::checkAndAlert($newId);
            } catch (\Throwable $e) {
                Logger::error(
                    'threshold.check_failed',
                    array(
                        'registration_id' => $newId,
                        'error'           => $e->getMessage(),
                    )
                );
            }
        }

        return $res->json(
            array(
                'success' => true,
                'id'      => $newId,
            ),
            201
        );
    }

    // -----------------------------------------------------------------
    // Dispatchers
    // -----------------------------------------------------------------

    /**
     * @param array<string, mixed> $fields
     */
    private function insertRegistration( array $fields ): int {
        $errors = $this->validateRegistration($fields);
        if ($errors !== array()) {
            throw new \InvalidArgumentException($this->formatErrors($errors));
        }
        [$typed, $dynamic] = $this->split($fields, self::REGISTRATION_FIELDS);
        $id = Registration::insert($typed + array( 'dynamic_data' => $dynamic ));
        // Audit trail — every new registration starts as 'pending'.
        StatusLogger::log(
            StatusHistory::ENTITY_REGISTRATION,
            $id,
            StatusHistory::FIELD_STATUS,
            null,
            'pending',
            null,
            'Initial submission'
        );
        return $id;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function insertContact( array $fields ): int {
        $errors = $this->validateContact($fields);
        if ($errors !== array()) {
            throw new \InvalidArgumentException($this->formatErrors($errors));
        }
        [$typed, $dynamic] = $this->split($fields, self::CONTACT_FIELDS);
        $id = Contact::insert($typed + array( 'dynamic_data' => $dynamic ));
        StatusLogger::log(
            StatusHistory::ENTITY_CONTACT,
            $id,
            StatusHistory::FIELD_STATUS,
            null,
            'pending',
            null,
            'Initial submission'
        );
        return $id;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function insertApplication( array $fields, string $type ): int {
        $errors = $this->validateApplication($fields);
        if ($errors !== array()) {
            throw new \InvalidArgumentException($this->formatErrors($errors));
        }
        [$typed, $dynamic] = $this->split($fields, self::APPLICATION_FIELDS);
        $id = Application::insert(
            $typed + array(
                'type' => $type,
                'dynamic_data' => $dynamic,
            )
        );
        StatusLogger::log(
            StatusHistory::ENTITY_APPLICATION,
            $id,
            StatusHistory::FIELD_STATUS,
            null,
            'pending',
            null,
            'Initial submission (' . $type . ')'
        );
        return $id;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function insertCustom( array $fields, string $slug ): int {
        if ($slug === '') {
            throw new \InvalidArgumentException('Custom target requires a slug.');
        }
        $endpoint = CustomEndpoint::findBySlug($slug);
        if ($endpoint === null) {
            throw new \InvalidArgumentException("Unknown custom endpoint slug: {$slug}");
        }
        $id = CustomSubmission::insert($endpoint['id'], $fields);
        StatusLogger::log(
            StatusHistory::ENTITY_CUSTOM_SUBMISSION,
            $id,
            StatusHistory::FIELD_STATUS,
            null,
            'pending',
            null,
            "Initial submission (custom: {$slug})"
        );
        return $id;
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Split the incoming fields into the typed-column subset and the
     * dynamic_data remainder. Only scalar values go to dynamic_data
     * (arrays/nested objects are kept on the typed side as JSON strings
     * only when the field is whitelisted; we don't have any such fields
     * today, so we just keep them on the dynamic side).
     *
     * @param array<string, mixed> $fields
     * @param array<int, string>   $whitelist
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function split( array $fields, array $whitelist ): array {
        $typed    = array();
        $dynamic  = array();
        $whitelistSet = array_flip($whitelist);
        foreach ($fields as $k => $v) {
            if (isset($whitelistSet[ $k ])) {
                $typed[ $k ] = $v;
            } elseif (is_scalar($v) || $v === null) {
                $dynamic[ $k ] = $v;
            } else {
                $dynamic[ $k ] = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }
        return array( $typed, $dynamic );
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, string>
     */
    private function validateRegistration( array $fields ): array {
        $errors = array();
        $name      = trim((string) ( $fields['name'] ?? '' ));
        $email     = trim((string) ( $fields['email'] ?? '' ));
        $course    = trim((string) ( $fields['course'] ?? '' ));
        $startDate = trim((string) ( $fields['start_date'] ?? '' ));
        $endDate   = trim((string) ( $fields['end_date'] ?? '' ));
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'A valid email is required.';
        }
        if ($course === '') {
            $errors['course'] = 'Course is required.';
        }
        if ($startDate === '') {
            $errors['start_date'] = 'Start date is required.';
        }
        if ($endDate === '') {
            $errors['end_date'] = 'End date is required.';
        }
        return $errors;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, string>
     */
    private function validateContact( array $fields ): array {
        $errors = array();
        $email = trim((string) ( $fields['email'] ?? '' ));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'A valid email is required.';
        }
        $name = trim((string) ( $fields['name'] ?? '' ));
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        return $errors;
    }

    /**
     * @param array<string, mixed> $fields
     * @return array<string, string>
     */
    private function validateApplication( array $fields ): array {
        $errors = array();
        $email = trim((string) ( $fields['email'] ?? '' ));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'A valid email is required.';
        }
        $name = trim((string) ( $fields['name'] ?? '' ));
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        return $errors;
    }

    /**
     * @param array<string, string> $errors
     */
    private function formatErrors( array $errors ): string {
        $parts = array();
        foreach ($errors as $field => $msg) {
            $parts[] = "{$field}: {$msg}";
        }
        return 'Validation failed — ' . implode('; ', $parts);
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function error( Response $res, int $status, string $code, string $message, array $extra = array() ): Response {
        return $res->json(
            array_merge(
                array(
                    'success' => false,
                    'error'   => $code,
                    'message' => $message,
                ),
                $extra
            ),
            $status
        );
    }
}

<?php

/**
 * IMedia Registration — FormRouteController.
 *
 * Phase 4: list + add + delete (per the open-question answer — no edit).
 * Keeps route-management concerns separate from the main Settings form.
 *
 * Per php-pro: strict types, readonly controller.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Config, Request, Response, Session};
use App\Models\{CustomEndpoint, FormRoute};

final readonly class FormRouteController {
    public function add( Request $req, Response $res ): Response {
        $formId     = (int) $req->input('form_id', 0);
        $targetType = (string) $req->input('target_type', '');
        $targetSlug = $this->nullIfEmpty((string) $req->input('target_slug', ''));

        $errors = self::validate($formId, $targetType, $targetSlug);
        if ($errors !== array()) {
            Session::flash('errors', $errors);
            Session::flash('flash_error', 'Could not add the route: ' . implode('; ', $errors));
            return $res->redirect($this->baseUrl() . '/admin/settings');
        }

        FormRoute::upsert($formId, $targetType, $targetSlug);
        Session::flash('flash', 'Form route for form_id ' . $formId . ' added.');
        return $res->redirect($this->baseUrl() . '/admin/settings');
    }

    public function delete( Request $req, Response $res ): Response {
        $formId = (int) $req->input('form_id', 0);
        if ($formId <= 0) {
            Session::flash('flash_error', 'Invalid form_id.');
            return $res->redirect($this->baseUrl() . '/admin/settings');
        }
        FormRoute::delete($formId);
        Session::flash('flash', 'Form route for form_id ' . $formId . ' removed.');
        return $res->redirect($this->baseUrl() . '/admin/settings');
    }

    /**
     * @return array<int, string>
     */
    private static function validate( int $formId, string $targetType, ?string $targetSlug ): array {
        $errors = array();
        if ($formId <= 0) {
            $errors[] = 'form_id must be a positive integer.';
        }
        $validTargets = array(
            FormRoute::TARGET_REGISTRATION,
            FormRoute::TARGET_CONTACT,
            FormRoute::TARGET_OJT,
            FormRoute::TARGET_TRAINER,
            FormRoute::TARGET_CUSTOM,
        );
        if (! in_array($targetType, $validTargets, true)) {
            $errors[] = 'target_type is invalid.';
        }
        if ($targetType === FormRoute::TARGET_CUSTOM) {
            if ($targetSlug === null || $targetSlug === '') {
                $errors[] = 'target_slug is required for target_type=custom.';
            } else {
                $endpoint = CustomEndpoint::findBySlug($targetSlug);
                if ($endpoint === null) {
                    $errors[] = "No custom endpoint with slug '{$targetSlug}' exists.";
                }
            }
        }
        return $errors;
    }

    private function nullIfEmpty( string $s ): ?string {
        $s = trim($s);
        return $s === '' ? null : $s;
    }

    private function baseUrl(): string {
        return rtrim((string) Config::get('BASE_URL', ''), '/');
    }
}

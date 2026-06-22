<?php

/**
 * IMedia Registration — RegistrationsController.
 *
 * Phase 3: list (with filter + pagination + bulk status), new, create,
 * view (read-only), edit, update (with status-history + threshold
 * check), delete (soft), restore (with status picker), alumni list.
 *
 * Phase 5: resume upload on create/update (multipart form), download
 * via /admin/registrations/{id}/resume, and the resume_path is shown
 * on the read-only view.
 *
 * Per php-pro: strict types, readonly controller, all SQL in models.
 * Per wordpress-pro: CSRF on every state-changing form post, output
 * escaping in views, capability check via AdminAuth middleware.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Config, Request, Response, Session, View};
use App\Models\{Registration, StatusHistory};
use App\Services\{FileStorage, StatusLogger, ThresholdChecker};

final readonly class RegistrationsController
{
    private const PER_PAGE = 25;

    public function index(Request $req, Response $res): Response
    {
        $filters = [
            'status'    => (string) $req->query('status', ''),
            'course'    => (string) $req->query('course', ''),
            'search'    => (string) $req->query('search', ''),
            'date_from' => (string) $req->query('date_from', ''),
            'date_to'   => (string) $req->query('date_to', ''),
        ];
        $page = max(1, (int) $req->query('page', 1));

        $result = Registration::paginate($filters, $page, self::PER_PAGE);

        return $res->view('admin.registrations.list', [
            '__title'    => 'Registrations',
            'baseUrl'    => $this->baseUrl(),
            'filters'    => $filters,
            'page'       => $result['page'],
            'pages'      => $result['pages'],
            'total'      => $result['total'],
            'rows'       => $result['rows'],
            'perPage'    => $result['perPage'],
            'statuses'   => Registration::STATUSES,
            'courses'    => $this->distinctCourses(),
            'csrf'       => \App\Core\Csrf::token(),
            'flash'      => Session::pullFlash('flash'),
            'flashError' => Session::pullFlash('flash_error'),
        ], 'admin');
    }

    public function newForm(Request $req, Response $res): Response
    {
        return $res->view('admin.registrations.edit', [
            '__title'    => 'New Registration',
            'baseUrl'    => $this->baseUrl(),
            'mode'       => 'create',
            'row'        => self::emptyRow(),
            'statuses'   => Registration::STATUSES,
            'payments'   => Registration::PAYMENT_STATUSES,
            'csrf'       => \App\Core\Csrf::token(),
            'action'     => $this->baseUrl() . '/admin/registrations',
            'submitText' => 'Create registration',
            'errors'     => View::errors(),
            'errorMsg'   => View::errorMessage(),
        ], 'admin');
    }

    public function create(Request $req, Response $res): Response
    {
        $input = self::readInput($req);
        $errors = self::validateInput($input);
        if ($errors !== []) {
            Session::flash('_old', $input);
            Session::flash('errors', $errors);
            Session::flash('error', 'Please correct the errors below.');
            return $res->redirect($this->baseUrl() . '/admin/registrations/new');
        }

        $id = Registration::insert($input + ['dynamic_data' => []]);
        StatusLogger::log(StatusHistory::ENTITY_REGISTRATION, $id, StatusHistory::FIELD_STATUS, null, $input['status'], null, 'Created via admin');
        try {
            ThresholdChecker::checkAndAlert($id);
        } catch (\Throwable $e) {
            \App\Core\Logger::error('threshold.check_failed', [
                'registration_id' => $id,
                'error'           => $e->getMessage(),
            ]);
        }

        $resumeFlash = self::handleResumeUpload($req, $id);
        if ($resumeFlash !== null) {
            Session::flash($resumeFlash['kind'] === 'error' ? 'flash_error' : 'flash', $resumeFlash['message']);
        }

        Session::flash('flash', 'Registration #' . $id . ' created.');
        return $res->redirect($this->baseUrl() . '/admin/registrations/' . $id);
    }

    public function view(Request $req, Response $res): Response
    {
        $id = (int) $req->param('id');
        $row = Registration::find($id);
        if ($row === null) {
            return $res->error(404, 'Registration not found.');
        }
        $history = StatusHistory::forEntity(StatusHistory::ENTITY_REGISTRATION, $id);
        return $res->view('admin.registrations.view', [
            '__title' => 'Registration #' . $id,
            'baseUrl' => $this->baseUrl(),
            'row'     => $row,
            'history' => $history,
            'flash'   => Session::pullFlash('flash'),
        ], 'admin');
    }

    public function edit(Request $req, Response $res): Response
    {
        $id = (int) $req->param('id');
        $row = Registration::find($id);
        if ($row === null) {
            return $res->error(404, 'Registration not found.');
        }
        return $res->view('admin.registrations.edit', [
            '__title'    => 'Edit Registration #' . $id,
            'baseUrl'    => $this->baseUrl(),
            'mode'       => 'edit',
            'id'         => $id,
            'row'        => $row,
            'statuses'   => Registration::STATUSES,
            'payments'   => Registration::PAYMENT_STATUSES,
            'csrf'       => \App\Core\Csrf::token(),
            'action'     => $this->baseUrl() . '/admin/registrations/' . $id,
            'submitText' => 'Save changes',
            'errors'     => View::errors(),
            'errorMsg'   => View::errorMessage(),
        ], 'admin');
    }

    public function update(Request $req, Response $res): Response
    {
        $id = (int) $req->param('id');
        $row = Registration::find($id);
        if ($row === null) {
            return $res->error(404, 'Registration not found.');
        }

        $input = self::readInput($req);
        // If dynamic_data is a JSON string from the textarea, decode it.
        if (isset($input['dynamic_data']) && is_string($input['dynamic_data'])) {
            $decoded = json_decode((string) $input['dynamic_data'], true);
            $input['dynamic_data'] = is_array($decoded) ? $decoded : [];
        }
        $errors = self::validateInput($input, $id);
        if ($errors !== []) {
            Session::flash('_old', $input);
            Session::flash('errors', $errors);
            Session::flash('error', 'Please correct the errors below.');
            return $res->redirect($this->baseUrl() . '/admin/registrations/' . $id . '/edit');
        }

        $result = Registration::update($id, $input);

        // Status-history: log every change to status or payment_status.
        if ($result['changed']) {
            $before = $result['before'] ?? [];
            $after  = $result['after']  ?? [];
            if (($before['status'] ?? null) !== ($after['status'] ?? null)) {
                StatusLogger::log(
                    StatusHistory::ENTITY_REGISTRATION,
                    $id,
                    StatusHistory::FIELD_STATUS,
                    (string) ($before['status'] ?? ''),
                    (string) ($after['status'] ?? ''),
                    null,
                    'Status changed via admin edit'
                );
            }
            if (($before['payment_status'] ?? null) !== ($after['payment_status'] ?? null)) {
                StatusLogger::log(
                    StatusHistory::ENTITY_REGISTRATION,
                    $id,
                    StatusHistory::FIELD_PAYMENT_STATUS,
                    (string) ($before['payment_status'] ?? ''),
                    (string) ($after['payment_status'] ?? ''),
                    null,
                    'Payment status changed via admin edit'
                );
            }
            // Threshold check on confirm transition.
            if (($before['status'] ?? null) !== 'confirm' && ($after['status'] ?? null) === 'confirm') {
                try {
                    ThresholdChecker::checkAndAlert($id);
                } catch (\Throwable $e) {
                    \App\Core\Logger::error('threshold.check_failed', [
                        'registration_id' => $id,
                        'error'           => $e->getMessage(),
                    ]);
                }
            }
        }

        $resumeFlash = self::handleResumeUpload($req, $id);
        if ($resumeFlash !== null) {
            Session::flash($resumeFlash['kind'] === 'error' ? 'flash_error' : 'flash', $resumeFlash['message']);
        }

        Session::flash('flash', 'Registration #' . $id . ' updated.');
        return $res->redirect($this->baseUrl() . '/admin/registrations/' . $id);
    }

    public function delete(Request $req, Response $res): Response
    {
        $id = (int) $req->param('id');
        $row = Registration::find($id);
        if ($row === null) {
            return $res->error(404, 'Registration not found.');
        }
        Registration::softDelete($id);
        StatusLogger::log(
            StatusHistory::ENTITY_REGISTRATION,
            $id,
            StatusHistory::FIELD_STATUS,
            (string) ($row['status'] ?? ''),
            (string) ($row['status'] ?? ''),
            null,
            'Soft-deleted (moved to alumni)'
        );
        Session::flash('flash', 'Registration #' . $id . ' moved to alumni.');
        return $res->redirect($this->baseUrl() . '/admin/registrations');
    }

    public function restore(Request $req, Response $res): Response
    {
        $id = (int) $req->param('id');
        $newStatus = (string) $req->input('new_status', 'pending');
        if (!in_array($newStatus, Registration::STATUSES, true)) {
            $newStatus = 'pending';
        }
        $row = Registration::find($id);
        if ($row === null) {
            return $res->error(404, 'Registration not found.');
        }
        Registration::restore($id, $newStatus);
        StatusLogger::log(
            StatusHistory::ENTITY_REGISTRATION,
            $id,
            StatusHistory::FIELD_STATUS,
            null,
            $newStatus,
            null,
            'Restored from alumni'
        );
        if ($newStatus === 'confirm') {
            try {
                ThresholdChecker::checkAndAlert($id);
            } catch (\Throwable $e) {
                \App\Core\Logger::error('threshold.check_failed', [
                    'registration_id' => $id,
                    'error'           => $e->getMessage(),
                ]);
            }
        }
        Session::flash('flash', 'Registration #' . $id . ' restored with status "' . $newStatus . '".');
        return $res->redirect($this->baseUrl() . '/admin/alumni');
    }

    public function bulkStatus(Request $req, Response $res): Response
    {
        $ids = $req->input('ids', []);
        $newStatus = (string) $req->input('new_status', '');
        if (!is_array($ids) || $ids === [] || !in_array($newStatus, Registration::STATUSES, true)) {
            Session::flash('flash_error', 'Invalid bulk request.');
            return $res->redirect($this->baseUrl() . '/admin/registrations');
        }
        $intIds = array_values(array_filter(array_map('intval', $ids), static fn ($i) => $i > 0));
        $count = Registration::bulkUpdateStatus($intIds, $newStatus);
        foreach ($intIds as $id) {
            StatusLogger::log(
                StatusHistory::ENTITY_REGISTRATION,
                $id,
                StatusHistory::FIELD_STATUS,
                null,
                $newStatus,
                null,
                'Bulk status change via admin'
            );
            if ($newStatus === 'confirm') {
                try {
                    ThresholdChecker::checkAndAlert($id);
                } catch (\Throwable $e) {
                    \App\Core\Logger::error('threshold.check_failed', [
                        'registration_id' => $id,
                        'error'           => $e->getMessage(),
                    ]);
                }
            }
        }
        Session::flash('flash', $count . ' registration(s) set to "' . $newStatus . '".');
        return $res->redirect($this->baseUrl() . '/admin/registrations');
    }

    public function alumni(Request $req, Response $res): Response
    {
        $filters = [
            'search' => (string) $req->query('search', ''),
        ];
        $page = max(1, (int) $req->query('page', 1));
        $result = Registration::alumni($filters, $page, self::PER_PAGE);

        return $res->view('admin.alumni', [
            '__title'    => 'Alumni (Soft-deleted)',
            'baseUrl'    => $this->baseUrl(),
            'filters'    => $filters,
            'page'       => $result['page'],
            'pages'      => $result['pages'],
            'total'      => $result['total'],
            'rows'       => $result['rows'],
            'perPage'    => $result['perPage'],
            'statuses'   => Registration::STATUSES,
            'csrf'       => \App\Core\Csrf::token(),
            'flash'      => Session::pullFlash('flash'),
            'flashError' => Session::pullFlash('flash_error'),
        ], 'admin');
    }

    /**
     * Phase 5: stream the resume file for one registration. Admin-only.
     * Renders inline so the browser can preview a PDF.
     */
    public function downloadResume(Request $req, Response $res): Response
    {
        $id = (int) $req->param('id');
        $rel = Registration::getResumePath($id);
        if ($rel === null) {
            return $res->error(404, 'No resume attached to this registration.');
        }
        $abs = FileStorage::absolutePath($rel);
        if ($abs === null || !is_file($abs)) {
            return $res->error(404, 'Resume file is missing on disk.');
        }

        $ext  = strtolower((string) pathinfo($abs, PATHINFO_EXTENSION));
        $mime = self::mimeForExt($ext);
        $filename = 'registration-' . $id . '-resume.' . $ext;

        $res->streamFile($abs, $mime, 'inline; filename="' . $filename . '"');
        exit;
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Best-effort resume upload handling. Returns null if no file was
     * submitted, or a flash-message array { kind: 'flash'|'error', message }.
     *
     * @return array{kind: string, message: string}|null
     */
    private static function handleResumeUpload(Request $req, int $registrationId): ?array
    {
        $file = $req->file('resume');
        if (!is_array($file) || (int) ($file['error'] ?? -1) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        try {
            $stored = FileStorage::handleResumeUpload($registrationId, $file);
            Registration::setResumePath($registrationId, $stored['path']);
            return [
                'kind'    => 'flash',
                'message' => 'Resume uploaded (' . self::humanSize($stored['size']) . ').',
            ];
        } catch (\Throwable $e) {
            \App\Core\Logger::warning('registration.resume_upload_failed', [
                'registration_id' => $registrationId,
                'error'           => $e->getMessage(),
            ]);
            return [
                'kind'    => 'error',
                'message' => 'Resume upload failed: ' . $e->getMessage(),
            ];
        }
    }

    private static function mimeForExt(string $ext): string
    {
        return match ($ext) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };
    }

    private static function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return number_format($bytes / 1048576, 2) . ' MB';
    }

    /**
     * @return array<string, mixed>
     */
    private static function readInput(Request $req): array
    {
        $input = (array) $req->input('reg', []);
        // The form uses nested names (reg[name], reg[email], ...).
        return $input;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    private static function validateInput(array $input, ?int $id = null): array
    {
        $errors = [];
        $name   = trim((string) ($input['name']   ?? ''));
        $email  = trim((string) ($input['email']  ?? ''));
        $course = trim((string) ($input['course'] ?? ''));
        $status = (string) ($input['status']  ?? 'pending');
        $payment = (string) ($input['payment_status'] ?? 'pending');
        $paidAmount = $input['paid_amount'] ?? null;
        $paidAt     = $input['paid_at']     ?? null;
        $remark     = $input['remark']      ?? null;

        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'A valid email is required.';
        }
        if ($course === '') {
            $errors['course'] = 'Course is required.';
        }
        if (!in_array($status, Registration::STATUSES, true)) {
            $errors['status'] = 'Invalid status.';
        }
        if (!in_array($payment, Registration::PAYMENT_STATUSES, true)) {
            $errors['payment_status'] = 'Invalid payment status.';
        }
        if ($payment !== 'pending') {
            if ($paidAmount === null || $paidAmount === '' || !is_numeric($paidAmount)) {
                $errors['paid_amount'] = 'Paid amount is required for non-pending payments.';
            }
            if ($paidAt === null || $paidAt === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $paidAt)) {
                $errors['paid_at'] = 'Paid date (YYYY-MM-DD) is required for non-pending payments.';
            }
            if ($remark === null || trim((string) $remark) === '') {
                $errors['remark'] = 'A remark is required for non-pending payments.';
            }
        }
        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyRow(): array
    {
        return [
            'id'             => null,
            'name'           => '',
            'mobile'         => '',
            'email'          => '',
            'address'        => '',
            'course'         => '',
            'start_date'     => date('Y-m-d'),
            'end_date'       => date('Y-m-d', strtotime('+30 days')),
            'status'         => 'pending',
            'payment_status' => 'pending',
            'paid_amount'    => '',
            'paid_at'        => '',
            'remark'         => '',
            'dynamic_data'   => [],
            'resume_path'    => null,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function distinctCourses(): array
    {
        try {
            return Registration::distinctCourses();
        } catch (\Throwable) {
            return [];
        }
    }

    private function baseUrl(): string
    {
        return rtrim((string) Config::get('BASE_URL', ''), '/');
    }
}

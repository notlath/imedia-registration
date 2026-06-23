<?php

/**
 * IMedia Registration — SettingsController.
 *
 * Phase 4: the big one. One show form, one save action, one test-email
 * action. The form has 6 sections (general, registration email, admin
 * notification, threshold alert, SMTP, form routes).
 *
 * Per php-pro: strict types, readonly controller.
 * Per wordpress-pro: SMTP password is write-only — never displayed back.
 *   The HMAC shared secret is also write-only for the same reason.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Config, Csrf, Request, Response, Session};
use App\Models\{FormRoute, OutboxEmail as OutboxModel, Setting};

final readonly class SettingsController {
    public function show( Request $req, Response $res ): Response {
        $settings = Setting::all();
        $routes   = FormRoute::all();

        return $res->view(
            'admin.settings',
            array(
                '__title'  => 'Settings',
                'baseUrl'  => $this->baseUrl(),
                'settings' => $settings,
                'routes'   => $routes,
                'csrf'     => Csrf::token(),
                'flash'    => Session::pullFlash('flash'),
                'flashErr' => Session::pullFlash('flash_error'),
            ),
            'admin'
        );
    }

    public function save( Request $req, Response $res ): Response {
        $errors = self::validate($req);
        if ($errors !== array()) {
            Session::flash('errors', $errors);
            Session::flash('error', 'Please correct the errors below.');
            return $res->redirect($this->baseUrl() . '/admin/settings');
        }

        $body = $req->body;

        // ----- General -----
        self::putIfSet('site_name', self::scalarString($body, 'site_name'));
        self::putIfSet('alert_threshold', self::scalarInt($body, 'alert_threshold'));

        // ----- Registration email -----
        self::putIfSet('email_template_subject', self::scalarString($body, 'email_template_subject'));
        self::putIfSet('email_template_body', self::scalarString($body, 'email_template_body'));
        self::putIfSet('email_template_enabled', self::scalarBool($body, 'email_template_enabled'));

        // ----- Admin notification -----
        self::putIfSet('admin_notification_enabled', self::scalarBool($body, 'admin_notification_enabled'));
        self::putIfSet('admin_notification_to', self::scalarString($body, 'admin_notification_to'));
        self::putIfSet('admin_notification_subject', self::scalarString($body, 'admin_notification_subject'));
        self::putIfSet('admin_notification_body', self::scalarString($body, 'admin_notification_body'));

        // ----- Threshold alert -----
        self::putIfSet('threshold_alert_enabled', self::scalarBool($body, 'threshold_alert_enabled'));
        self::putIfSet('threshold_alert_to', self::scalarString($body, 'threshold_alert_to'));
        self::putIfSet('threshold_alert_subject', self::scalarString($body, 'threshold_alert_subject'));
        self::putIfSet('threshold_alert_body', self::scalarString($body, 'threshold_alert_body'));

        // ----- SMTP (passwords and secrets are write-only) -----
        self::putIfSet('smtp_host', self::scalarString($body, 'smtp_host'));
        self::putIfSet('smtp_port', self::scalarInt($body, 'smtp_port', 587));
        self::putIfSet('smtp_user', self::scalarString($body, 'smtp_user'));
        self::putIfSet('smtp_from_name', self::scalarString($body, 'smtp_from_name'));
        self::putIfSet('smtp_from_email', self::scalarString($body, 'smtp_from_email'));
        self::putIfSet('smtp_secure', self::scalarBool($body, 'smtp_secure'));

        $smtpPass = self::scalarString($body, 'smtp_pass');
        if ($smtpPass !== '') {
            Setting::put('smtp_pass', $smtpPass);
        }
        $hmacSecret = self::scalarString($body, 'hmac_shared_secret');
        if ($hmacSecret !== '') {
            Setting::put('hmac_shared_secret', $hmacSecret);
        }

        // Force re-read of the cached row for the very next request.
        Setting::refresh();

        Session::flash('flash', 'Settings saved.');
        return $res->redirect($this->baseUrl() . '/admin/settings');
    }

    public function testEmail( Request $req, Response $res ): Response {
        $to = (string) Setting::get('admin_notification_to', '');
        if ($to === '') {
            $to = (string) Setting::get('smtp_user', '');
        }
        if ($to === '') {
            $to = (string) Config::get('MAIL_FROM_EMAIL', '');
        }
        if ($to === '') {
            Session::flash('flash_error', 'No recipient configured. Set admin_notification_to or smtp_user first.');
            return $res->redirect($this->baseUrl() . '/admin/settings');
        }
        $body  = '<p>Test email from IMedia Registration.</p><p>Sent at: ' . htmlspecialchars(date('c'), ENT_QUOTES, 'UTF-8') . '</p>';
        $id = OutboxModel::enqueue($to, 'IMedia Registration: test email', $body, array( 'kind' => 'test_email' ));
        Session::flash('flash', 'Test email #' . $id . ' queued for ' . htmlspecialchars($to, ENT_QUOTES, 'UTF-8') . '. (Sending is wired in Phase 5.)');
        return $res->redirect($this->baseUrl() . '/admin/settings');
    }

    // -----------------------------------------------------------------
    // Validation + helpers
    // -----------------------------------------------------------------

    /**
     * @return array<string, string>
     */
    private static function validate( Request $req ): array {
        $errors = array();
        $body   = $req->body;

        $siteName = self::scalarString($body, 'site_name');
        if ($siteName === '') {
            $errors['site_name'] = 'Site name is required.';
        } elseif (mb_strlen($siteName) > 255) {
            $errors['site_name'] = 'Site name must be ≤ 255 characters.';
        }

        $threshold = (int) ( $body['alert_threshold'] ?? 0 );
        if ($threshold < 1 || $threshold > 9999) {
            $errors['alert_threshold'] = 'Alert threshold must be between 1 and 9999.';
        }

        $emailTemplateEnabled = self::scalarBool($body, 'email_template_enabled');
        if ($emailTemplateEnabled) {
            if (self::scalarString($body, 'email_template_subject') === '') {
                $errors['email_template_subject'] = 'Subject is required when the template is enabled.';
            }
        }

        $adminNotifEnabled = self::scalarBool($body, 'admin_notification_enabled');
        if ($adminNotifEnabled) {
            $to = self::scalarString($body, 'admin_notification_to');
            if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
                $errors['admin_notification_to'] = 'A valid admin email is required when notifications are enabled.';
            }
        }

        $thresholdAlertEnabled = self::scalarBool($body, 'threshold_alert_enabled');
        if ($thresholdAlertEnabled) {
            $to = self::scalarString($body, 'threshold_alert_to');
            if ($to === '' || filter_var($to, FILTER_VALIDATE_EMAIL) === false) {
                $errors['threshold_alert_to'] = 'A valid threshold alert email is required when threshold alerts are enabled.';
            }
        }

        $smtpHost = self::scalarString($body, 'smtp_host');
        if ($smtpHost !== '') {
            $smtpPort = (int) ( $body['smtp_port'] ?? 0 );
            if ($smtpPort < 1 || $smtpPort > 65535) {
                $errors['smtp_port'] = 'SMTP port must be between 1 and 65535.';
            }
        }
        $smtpFrom = self::scalarString($body, 'smtp_from_email');
        if ($smtpFrom !== '' && filter_var($smtpFrom, FILTER_VALIDATE_EMAIL) === false) {
            $errors['smtp_from_email'] = 'A valid From email is required when set.';
        }

        return $errors;
    }

    private static function putIfSet( string $key, mixed $value ): void {
        // Empty strings become NULL for non-required string columns; leave
        // them as '' for the case where '' is a valid stored value.
        Setting::put($key, $value);
    }

    private static function scalarString( array $body, string $key ): string {
        $v = $body[ $key ] ?? '';
        return is_string($v) ? trim($v) : (string) $v;
    }

    private static function scalarInt( array $body, string $key, int $default = 0 ): int {
        $v = $body[ $key ] ?? $default;
        return is_numeric($v) ? (int) $v : $default;
    }

    private static function scalarBool( array $body, string $key ): int {
        $v = $body[ $key ] ?? 0;
        if (is_string($v)) {
            $v = strtolower($v);
            if ($v === '1' || $v === 'true' || $v === 'on' || $v === 'yes') {
                return 1;
            }
            return 0;
        }
        return $v ? 1 : 0;
    }

    private function baseUrl(): string {
        return rtrim((string) Config::get('BASE_URL', ''), '/');
    }
}

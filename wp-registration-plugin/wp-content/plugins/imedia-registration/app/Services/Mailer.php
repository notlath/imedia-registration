<?php

/**
 * IMedia Registration — Mailer service.
 *
 * Sends one email via SMTP using the vendored PHPMailer. Reads SMTP
 * config from the settings row. Returns true on success, false on
 * failure with the error string passed back via the second arg.
 *
 * Per php-pro: strict types, readonly where applicable.
 * Per wordpress-pro: write-only secret handling — never log the
 *   password, never echo it in errors.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\{Logger, PhpmailerLoader};
use App\Models\Setting;
use PHPMailer\PHPMailer\{Exception as PHPMailerException, PHPMailer};

final class Mailer
{
    public const MAX_ATTEMPTS = 3;
    public const TIMEOUT_SECONDS = 10;

    /**
     * Send one email. Returns true on success.
     * On failure, $error is set to a short reason code + message
     * (the password is never included).
     */
    public static function send(string $toEmail, string $subject, string $bodyHtml, ?string &$error = null): bool
    {
        $error = null;

        // ----- Load + validate settings -----
        $host = (string) Setting::get('smtp_host', '');
        if ($host === '') {
            $error = 'smtp_not_configured';
            return false;
        }
        $port        = (int) Setting::get('smtp_port', 587);
        $user        = (string) Setting::get('smtp_user', '');
        $pass        = (string) Setting::get('smtp_pass', '');
        $secure      = (int) Setting::get('smtp_secure', 0) === 1 ? 'tls' : '';
        $fromEmail   = (string) Setting::get('smtp_from_email', '');
        $fromName    = (string) Setting::get('smtp_from_name', '');
        $siteName    = (string) Setting::get('site_name', 'IMedia Registration');

        if ($fromEmail === '') {
            $fromEmail = $user !== '' ? $user : $toEmail;
        }
        if ($fromName === '') {
            $fromName = $siteName;
        }
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'invalid_recipient';
            return false;
        }

        // ----- Load PHPMailer -----
        try {
            PhpmailerLoader::load();
        } catch (\Throwable $e) {
            $error = 'phpmailer_load_failed: ' . $e->getMessage();
            Logger::error('mail.phpmailer_load_failed', ['error' => $e->getMessage()]);
            return false;
        }

        // ----- Build + send -----
        try {
            $mail = new PHPMailer();
            $mail->isSMTP();
            $mail->CharSet   = 'utf-8';
            $mail->Timeout   = self::TIMEOUT_SECONDS;
            $mail->Host      = $host;
            $mail->Port      = $port;
            $mail->SMTPAuth  = $user !== '';
            $mail->Username  = $user;
            $mail->Password  = $pass;
            $mail->SMTPSecure = $secure;
            $mail->AuthType  = 'LOGIN';
            $mail->Helo      = $host;

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail);
            $mail->Subject = $subject;
            $mail->Body    = $bodyHtml;
            $mail->isHTML(true);

            $mail->send();
            return true;
        } catch (PHPMailerException $e) {
            // Sanitize: never include the password in the error string.
            $msg = $e->getMessage();
            if ($pass !== '' && $pass !== '0') {
                $msg = str_replace($pass, '***', $msg);
            }
            $error = 'smtp_error: ' . $msg;
            Logger::error('mail.send_failed', [
                'to'      => $toEmail,
                'subject' => $subject,
                'error'   => $msg,
            ]);
            return false;
        } catch (\Throwable $e) {
            $error = 'send_failed: ' . $e->getMessage();
            Logger::error('mail.send_exception', [
                'to'    => $toEmail,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

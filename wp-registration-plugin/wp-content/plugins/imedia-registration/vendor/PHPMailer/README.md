# Vendored PHPMailer

This directory contains a **minimal, scoped** vendoring of
[PHPMailer](https://github.com/PHPMailer/PHPMailer) 6.x for the
IMedia Registration app. It is intentionally limited to the
public-API surface used by `App\Services\Mailer`.

## Files
- `src/PHPMailer.php` — main class. Methods used: `isSMTP`, `isHTML`,
  `CharSet`, `Host`, `Port`, `SMTPAuth`, `Username`, `Password`,
  `SMTPSecure`, `AuthType`, `Timeout`, `Subject`, `Body`, `setFrom`,
  `addAddress`, `send`.
- `src/SMTP.php` — SMTP wire protocol. Implements EHLO, STARTTLS,
  AUTH LOGIN, MAIL FROM, RCPT TO, DATA, QUIT. Subset of upstream.
- `src/Exception.php` — `PHPMailer\PHPMailer\Exception`. Shape
  matches upstream (extends `\Exception`).

## Why a vendored copy?
The plugin folder does not ship with Composer. Rather than add a
full dependency manager, we vendor only what we use. The shapes
(`class_exists('\PHPMailer\PHPMailer\PHPMailer')`,
`class_exists('\PHPMailer\PHPMailer\SMTP')`,
`class_exists('\PHPMailer\PHPMailer\Exception')`,
`catch (\PHPMailer\PHPMailer\Exception $e)`) match the upstream
PHPMailer 6.x namespace and method signatures, so this directory
can be replaced with the official `composer require phpmailer/phpmailer`
artifacts without any application-code change. Just delete this
folder, run `composer install` in the plugin root, and adjust the
autoloader in `app/Core/PhpmailerLoader.php` to skip the manual
`require_once`.

## What is NOT supported
- Attachments (`addAttachment`)
- CC / BCC / Reply-To
- DKIM signing
- HTML → plain-text auto-conversion
- Internationalized email addresses (SMTPUTF8)
- The `mail()` transport (we always use SMTP)

If you need any of these, swap this vendored copy for the upstream
PHPMailer 6.x release.

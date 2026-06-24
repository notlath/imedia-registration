<?php

/**
 * IMedia Registration — application configuration template.
 *
 * USAGE:
 *   1. Copy this file to `config.php` (sibling).
 *   2. Fill in the values for your cPanel MySQL database.
 *   3. Set the BASE_URL to your site.
 *   4. The HMAC secret must match the WordPress plugin's `imf_shared_secret` option
 *      (Settings > IMedia Registration > Shared Secret).
 *
 * SECURITY: `config.php` is gitignored. Do not commit credentials.
 */

declare(strict_types=1);

return [

    'APP_DEBUG'      => false,                                        // true to show error details (NEVER in prod)
    'BASE_URL'       => 'https://www.inventivemedia.com.ph/registration',

    // ----- Database (cPanel MySQL) -----
    'DB_HOST'        => 'localhost',                                  // cPanel: usually 'localhost'
    'DB_PORT'        => 3306,
    'DB_NAME'        => 'cpaneluser_imreg',                           // <-- replace
    'DB_USER'        => 'cpaneluser_imreg',                           // <-- replace
    'DB_PASS'        => 'CHANGE_ME_AT_INSTALL',                       // <-- replace
    'DB_CHARSET'     => 'utf8mb4',

    // ----- Session -----
    'SESSION_NAME'   => 'imreg_session',
    'SESSION_LIFETIME' => 7200,                                       // 2 hours
    'SESSION_SECURE' => true,                                         // require HTTPS
    'SESSION_HTTPONLY' => true,
    'SESSION_SAMESITE' => 'Lax',

    // ----- HMAC -----
    // The shared secret lives in the `settings` database table and is
    // configured via the admin UI (Settings > HMAC shared secret).
    // Generate with: php -r "echo bin2hex(random_bytes(32));"
    // Must match: WP Admin > Settings > IMedia Registration > Shared Secret.

    // ----- Mail (SMTP) -----
    // Most config should live in the `settings` table so admins can edit it via the UI.
    'MAIL_FROM_EMAIL' => 'noreply@inventivemedia.com.ph',

    // ----- Logging -----
    'LOG_PATH'       => __DIR__ . '/../storage/logs/app.log',
    'LOG_LEVEL'      => 'info',                                       // 'debug' | 'info' | 'warning' | 'error'
];

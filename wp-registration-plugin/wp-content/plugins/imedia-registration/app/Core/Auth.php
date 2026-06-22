<?php

/**
 * IMedia Registration — Authentication.
 *
 * Reads admins from the application's MySQL DB. Bcrypt verify via
 * password_verify. On success, regenerates the session ID to prevent
 * session fixation (php-pro).
 *
 * Per wordpress-pro: no raw $_SESSION access; everything goes through Session.
 */

declare(strict_types=1);

namespace App\Core;

use App\Models\Admin;

final class Auth
{
    private const SESSION_KEY = '_admin';

    /**
     * Try to authenticate the given credentials against the admins table.
     * Returns the admin row (id, name, email, role) on success, null on failure.
     *
     * @return array{id: int, name: string, email: string, role: string}|null
     */
    public static function attempt(string $email, string $password): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '' || $password === '') {
            return null;
        }

        $row = Admin::findByEmail($email);
        if ($row === null) {
            // Always run a dummy verify to keep timing constant.
            password_verify($password, '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidi');
            return null;
        }

        if (!password_verify($password, $row['password'])) {
            return null;
        }

        // Optional: re-hash if cost has changed.
        if (password_needs_rehash($row['password'], PASSWORD_BCRYPT)) {
            Admin::updatePassword($row['id'], password_hash($password, PASSWORD_BCRYPT));
        }

        $admin = [
            'id'    => $row['id'],
            'name'  => $row['name'],
            'email' => $row['email'],
            'role'  => $row['role'],
        ];

        // Prevent session fixation: regenerate ID on privilege change.
        Session::regenerate();
        Session::put(self::SESSION_KEY, $admin);

        Logger::info('auth.login.success', ['admin_id' => $admin['id'], 'email' => $admin['email']]);
        return $admin;
    }

    /**
     * Is there an authenticated admin in the current session?
     */
    public static function check(): bool
    {
        $admin = Session::get(self::SESSION_KEY);
        return is_array($admin) && isset($admin['id']);
    }

    /**
     * @return array{id: int, name: string, email: string, role: string}|null
     */
    public static function admin(): ?array
    {
        $admin = Session::get(self::SESSION_KEY);
        return is_array($admin) ? $admin : null;
    }

    public static function id(): ?int
    {
        $admin = self::admin();
        return $admin['id'] ?? null;
    }

    public static function logout(): void
    {
        if (self::check()) {
            Logger::info('auth.logout', ['admin_id' => self::id()]);
        }
        Session::destroy();
    }
}

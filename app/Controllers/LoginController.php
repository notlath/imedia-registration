<?php

/**
 * IMedia Registration — LoginController.
 *
 * Phase 2 endpoints:
 *   GET  /admin/login   showForm()  — render the form
 *   POST /admin/login   login()     — verify creds, start session, redirect
 *   POST /admin/logout  logout()    — destroy session, redirect
 *
 * Per php-pro: readonly controller, no globals, CSRF on POST.
 * Per wordpress-pro: redirects are validated to local paths only.
 * Phase 8: throttles failed logins. Per-IP (5 / 15 min) and per-account
 * (10 / 1 h) windows; the account also flips a DB-side lockout that
 * survives across logins even when the IP rotates.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Csrf, Logger, Request, Response, Session, View};
use App\Models\{Admin, LoginAttempt};

final readonly class LoginController {
    /**
     * Show the login form. If already logged in, redirect to /admin.
     */
    public function showForm( Request $req, Response $res ): Response {
        if (Auth::check()) {
            return $res->redirect($this->baseUrl() . '/admin');
        }
        return $res->view(
            'login',
            array(
                '__title'   => 'Sign in',
                'baseUrl'   => $this->baseUrl(),
                'flash'     => Session::pullFlash('login_error'),
                'prefill'   => (string) Session::pullFlash('login_email') ?? '',
            ),
            'public'
        );
    }

    /**
     * Verify the submitted credentials and sign the admin in.
     */
    public function login( Request $req, Response $res ): Response {
        if (! Csrf::verify((string) $req->input('_csrf'))) {
            Session::flash('login_error', 'Security check failed. Please try again.');
            return $res->redirect($this->baseUrl() . '/admin/login');
        }

        $email    = strtolower(trim((string) $req->input('email', '')));
        $password = (string) $req->input('password', '');
        $ip       = $req->ip();

        // ---- Per-IP throttle (fast reject of credential-stuffing) ----
        if (LoginAttempt::recentFailuresByIp($ip) >= LoginAttempt::IP_MAX_FAILURES) {
            Logger::warning(
                'auth.throttle.ip',
                array(
                    'ip' => $ip,
                    'email' => $email,
                )
            );
            return $res->error(429, 'Too many failed attempts from your network. Try again in a few minutes.');
        }

        // ---- Per-account lockout (survives IP rotation) ----
        $existing = Admin::findByEmail($email);
        if ($existing !== null && ! empty($existing['locked_until'])) {
            $until = strtotime((string) $existing['locked_until']);
            if ($until !== false && $until > time()) {
                Logger::warning(
                    'auth.throttle.locked',
                    array(
                        'admin_id' => $existing['id'],
                        'email' => $email,
                    )
                );
                return $res->error(429, 'Account temporarily locked due to repeated failed attempts. Try again later.');
            }
        }

        $admin = Auth::attempt($email, $password);
        if ($admin === null) {
            LoginAttempt::recordFailure($ip, $email);
            $emailFails = LoginAttempt::recentFailuresByEmail($email);
            if ($emailFails >= LoginAttempt::EMAIL_MAX_FAILURES && $existing !== null) {
                Admin::lock($existing['id'], ( new \DateTimeImmutable() )->modify('+1 hour'));
                Logger::warning(
                    'auth.throttle.lockout',
                    array(
                        'admin_id' => $existing['id'],
                        'email' => $email,
                    )
                );
            } else {
                Logger::info(
                    'auth.login.failed',
                    array(
                        'email' => $email,
                        'ip' => $ip,
                    )
                );
            }
            Session::flash('login_error', 'Invalid email or password.');
            Session::flash('login_email', $email);
            return $res->redirect($this->baseUrl() . '/admin/login');
        }

        // Successful login: clear the lockout and record a success row.
        if ($existing !== null) {
            Admin::lock($existing['id'], null);
        }
        LoginAttempt::recordSuccess($ip, $email);

        Session::flash('login_success', 'Welcome back, ' . $admin['name'] . '.');
        return $res->redirect($this->baseUrl() . '/admin');
    }

    /**
     * Sign out and redirect to the login page.
     */
    public function logout( Request $req, Response $res ): Response {
        if (! Csrf::verify((string) $req->input('_csrf'))) {
            return $res->error(419, 'Security check failed.');
        }
        Auth::logout();
        return $res->redirect($this->baseUrl() . '/admin/login');
    }

    private function baseUrl(): string {
        return rtrim((string) \App\Core\Config::get('BASE_URL', ''), '/');
    }
}

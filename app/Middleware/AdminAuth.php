<?php

/**
 * IMedia Registration — AdminAuth middleware.
 *
 * Redirects unauthenticated admins to /admin/login. Used on every
 * admin route via the route table.
 *
 * Per wordpress-pro: gates by Auth::check() (not by capability; this
 * is a single-tier admin app — all logged-in users are admins).
 */

declare(strict_types=1);

namespace App\Middleware;

use App\Core\{Auth, Config, Request, Response};

final class AdminAuth {
    public function __invoke( Request $req, Response $res, callable $next ): Response {
        if (! Auth::check()) {
            $base = rtrim((string) Config::get('BASE_URL', ''), '/');
            return $res->redirect($base . '/admin/login');
        }
        return $next($req, $res);
    }
}

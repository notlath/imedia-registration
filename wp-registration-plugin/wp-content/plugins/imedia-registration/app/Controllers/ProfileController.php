<?php

/**
 * IMedia Registration — ProfileController.
 *
 * Phase 4: self-edit form (name, email, password). Role is locked
 * (the controller never accepts a role change from this page; the
 * Admin::updateOwn helper also ignores it).
 *
 * Per php-pro: strict types, readonly controller.
 * Per wordpress-pro: password_verify on the current-password check.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Config, Csrf, Request, Response, Session, View};
use App\Models\Admin;

final readonly class ProfileController
{
    public function show(Request $req, Response $res): Response
    {
        $admin = Auth::admin();
        if ($admin === null) {
            return $res->redirect($this->baseUrl() . '/admin/login');
        }
        // Pull the freshest row (a name/email change elsewhere won't reflect
        // until we re-read).
        $fresh = Admin::find((int) $admin['id']) ?? $admin;
        $old = Session::getFlash('_old') ?? [];
        $old = is_array($old) ? $old : [];

        return $res->view('admin.profile', [
            '__title'  => 'My account',
            'baseUrl'  => $this->baseUrl(),
            'admin'    => $fresh,
            'old'      => $old,
            'csrf'     => Csrf::token(),
            'errors'   => View::errors(),
            'errorMsg' => View::errorMessage(),
            'flash'    => Session::pullFlash('flash'),
        ], 'admin');
    }

    public function update(Request $req, Response $res): Response
    {
        $admin = Auth::admin();
        if ($admin === null) {
            return $res->redirect($this->baseUrl() . '/admin/login');
        }
        $id         = (int) $admin['id'];
        $name       = trim((string) $req->input('name', ''));
        $email      = trim((string) $req->input('email', ''));
        $currentPw  = (string) $req->input('current_password', '');
        $newPw      = (string) $req->input('new_password', '');
        $confirmPw  = (string) $req->input('confirm_password', '');

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'A valid email is required.';
        }

        $wantsPasswordChange = ($newPw !== '' || $confirmPw !== '');
        if ($wantsPasswordChange) {
            if ($currentPw === '') {
                $errors['current_password'] = 'Current password is required to change it.';
            } else {
                $fresh = Admin::find($id);
                if ($fresh === null || !password_verify($currentPw, (string) $fresh['password'])) {
                    $errors['current_password'] = 'Current password is incorrect.';
                }
            }
            if (strlen($newPw) < 8) {
                $errors['new_password'] = 'New password must be at least 8 characters.';
            } elseif ($newPw !== $confirmPw) {
                $errors['confirm_password'] = 'New password and confirmation do not match.';
            }
        }

        if ($errors !== []) {
            Session::flash('_old', [
                'name'  => $name,
                'email' => $email,
            ]);
            Session::flash('errors', $errors);
            Session::flash('error', 'Please correct the errors below.');
            return $res->redirect($this->baseUrl() . '/admin/profile');
        }

        // The role is intentionally not passed — updateOwn blocks it.
        Admin::updateOwn(
            $id,
            $name,
            $email,
            $wantsPasswordChange ? $newPw : null
        );
        // Refresh session-stored admin (so the sidebar name updates).
        $fresh = Admin::find($id);
        if ($fresh !== null) {
            Session::put('_admin', [
                'id'    => $fresh['id'],
                'name'  => $fresh['name'],
                'email' => $fresh['email'],
                'role'  => $fresh['role'],
            ]);
        }
        Session::flash('flash', 'Account updated.');
        return $res->redirect($this->baseUrl() . '/admin/profile');
    }

    private function baseUrl(): string
    {
        return rtrim((string) Config::get('BASE_URL', ''), '/');
    }
}

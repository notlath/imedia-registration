<?php

/**
 * IMedia Registration — UsersController.
 *
 * Phase 4: full CRUD for admin users. Refuses to delete the last admin
 * or to delete the currently-logged-in admin.
 *
 * Per php-pro: strict types, readonly controller.
 * Per wordpress-pro: password_hash on create / password change.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Auth, Config, Csrf, Request, Response, Session, View};
use App\Models\Admin;

final readonly class UsersController {
    public function index( Request $req, Response $res ): Response {
        $admins   = Admin::all();
        $count    = Admin::count();
        $currentId = Auth::id();

        return $res->view(
            'admin.users.list',
            array(
                '__title'    => 'Admin users',
                'baseUrl'    => $this->baseUrl(),
                'admins'     => $admins,
                'count'      => $count,
                'currentId'  => $currentId,
                'csrf'       => Csrf::token(),
                'flash'      => Session::pullFlash('flash'),
                'flashError' => Session::pullFlash('flash_error'),
            ),
            'admin'
        );
    }

    public function newForm( Request $req, Response $res ): Response {
        return $res->view(
            'admin.users.edit',
            array(
                '__title'    => 'New admin',
                'baseUrl'    => $this->baseUrl(),
                'mode'       => 'create',
                'user'       => self::emptyUser(),
                'roles'      => Admin::ROLES,
                'action'     => $this->baseUrl() . '/admin/users',
                'submitText' => 'Create admin',
                'showPassword' => true,
                'csrf'       => Csrf::token(),
                'errors'     => View::errors(),
                'errorMsg'   => View::errorMessage(),
                'flash'      => Session::pullFlash('flash'),
                'flashError' => Session::pullFlash('flash_error'),
            ),
            'admin'
        );
    }

    public function create( Request $req, Response $res ): Response {
        $name     = trim((string) $req->input('name', ''));
        $email    = trim((string) $req->input('email', ''));
        $role     = (string) $req->input('role', 'admin');
        $password = (string) $req->input('password', '');

        $errors = self::validateCreate($name, $email, $role, $password);
        if ($errors !== array()) {
            Session::flash('_old', compact('name', 'email', 'role'));
            Session::flash('errors', $errors);
            Session::flash('error', 'Please correct the errors below.');
            return $res->redirect($this->baseUrl() . '/admin/users/new');
        }

        try {
            $id = Admin::create($name, $email, $password, $role);
        } catch (\PDOException $e) {
            Session::flash('_old', compact('name', 'email', 'role'));
            Session::flash('error', 'Could not create admin (email may be taken).');
            return $res->redirect($this->baseUrl() . '/admin/users/new');
        }

        Session::flash('flash', 'Admin "' . $name . '" created.');
        return $res->redirect($this->baseUrl() . '/admin/users/' . $id . '/edit');
    }

    public function edit( Request $req, Response $res ): Response {
        $id = (int) $req->param('id');
        $user = Admin::find($id);
        if ($user === null) {
            return $res->error(404, 'Admin not found.');
        }
        $isSelf = (int) $user['id'] === (int) ( Auth::id() ?? 0 );

        return $res->view(
            'admin.users.edit',
            array(
                '__title'      => 'Edit admin #' . $id,
                'baseUrl'      => $this->baseUrl(),
                'mode'         => 'edit',
                'id'           => $id,
                'user'         => $user,
                'roles'        => Admin::ROLES,
                'isSelf'       => $isSelf,
                'action'       => $this->baseUrl() . '/admin/users/' . $id,
                'submitText'   => 'Save changes',
                'showPassword' => true,
                'csrf'         => Csrf::token(),
                'errors'       => View::errors(),
                'errorMsg'     => View::errorMessage(),
            ),
            'admin'
        );
    }

    public function update( Request $req, Response $res ): Response {
        $id = (int) $req->param('id');
        $user = Admin::find($id);
        if ($user === null) {
            return $res->error(404, 'Admin not found.');
        }

        $isSelf     = (int) $user['id'] === (int) ( Auth::id() ?? 0 );
        $name       = trim((string) $req->input('name', ''));
        $email      = trim((string) $req->input('email', ''));
        $role       = (string) $req->input('role', $user['role']);
        $password   = (string) $req->input('password', '');

        $errors = self::validateUpdate($name, $email, $role, $password, $isSelf);
        if ($errors !== array()) {
            Session::flash('_old', compact('name', 'email', 'role'));
            Session::flash('errors', $errors);
            Session::flash('error', 'Please correct the errors below.');
            return $res->redirect($this->baseUrl() . '/admin/users/' . $id . '/edit');
        }

        // The role change is blocked for self-edits (validateUpdate enforces this).
        Admin::update(
            $id,
            $name,
            $email,
            $isSelf ? null : $role,
            $password !== '' ? $password : null
        );
        Session::flash('flash', 'Admin #' . $id . ' updated.');
        return $res->redirect($this->baseUrl() . '/admin/users/' . $id . '/edit');
    }

    public function delete( Request $req, Response $res ): Response {
        $id = (int) $req->param('id');
        if ($id === (int) ( Auth::id() ?? 0 )) {
            Session::flash('flash_error', 'You cannot delete your own account.');
            return $res->redirect($this->baseUrl() . '/admin/users');
        }
        if (Admin::count() <= 1) {
            Session::flash('flash_error', 'Cannot delete the last admin.');
            return $res->redirect($this->baseUrl() . '/admin/users');
        }
        Admin::delete($id);
        Session::flash('flash', 'Admin #' . $id . ' deleted.');
        return $res->redirect($this->baseUrl() . '/admin/users');
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * @return array<string, string>
     */
    private static function validateCreate( string $name, string $email, string $role, string $password ): array {
        return self::validateCommon($name, $email, $role, $password, false);
    }

    /**
     * @return array<string, string>
     */
    private static function validateUpdate( string $name, string $email, string $role, string $password, bool $isSelf ): array {
        return self::validateCommon($name, $email, $role, $password, $isSelf);
    }

    /**
     * @return array<string, string>
     */
    private static function validateCommon( string $name, string $email, string $role, string $password, bool $isSelf ): array {
        $errors = array();
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        }
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'A valid email is required.';
        }
        if (! in_array($role, Admin::ROLES, true)) {
            $errors['role'] = 'Invalid role.';
        } elseif ($isSelf && $role !== '') {
            // We pass the new role only if not self; if self, the form still
            // sends the current role back, which we ignore. Block any change
            // by validating it equals the current.
            $errors['role'] = 'You cannot change your own role.';
        }
        if ($password !== '' && strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }
        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyUser(): array {
        return array(
            'id'    => null,
            'name'  => '',
            'email' => '',
            'role'  => 'admin',
        );
    }

    private function baseUrl(): string {
        return rtrim((string) Config::get('BASE_URL', ''), '/');
    }
}

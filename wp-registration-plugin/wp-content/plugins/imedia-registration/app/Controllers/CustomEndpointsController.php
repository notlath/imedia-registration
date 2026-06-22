<?php

/**
 * IMedia Registration — CustomEndpointsController.
 *
 * Phase 4: full CRUD for the dynamic schema endpoints. Per the
 * open-question answer, this phase ships list + create + edit + delete +
 * submissions (read-only) for one endpoint.
 *
 * Per php-pro: strict types, readonly controller, all SQL in models.
 * Per wordpress-pro: CSRF + AdminAuth on every state-changing action.
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Config, Csrf, Request, Response, Session, View};
use App\Models\{CustomEndpoint, CustomSubmission};

final readonly class CustomEndpointsController
{
    private const PER_PAGE = 25;

    public function index(Request $req, Response $res): Response
    {
        $endpoints = CustomEndpoint::all();

        return $res->view('admin.custom-endpoints.list', [
            '__title'    => 'Custom Endpoints',
            'baseUrl'    => $this->baseUrl(),
            'endpoints'  => $endpoints,
            'csrf'       => Csrf::token(),
            'flash'      => Session::pullFlash('flash'),
            'flashError' => Session::pullFlash('flash_error'),
        ], 'admin');
    }

    public function newForm(Request $req, Response $res): Response
    {
        return $res->view('admin.custom-endpoints.edit', [
            '__title'    => 'New Custom Endpoint',
            'baseUrl'    => $this->baseUrl(),
            'mode'       => 'create',
            'endpoint'   => self::emptyEndpoint(),
            'action'     => $this->baseUrl() . '/admin/custom-endpoints',
            'submitText' => 'Create endpoint',
            'csrf'       => Csrf::token(),
            'errors'     => View::errors(),
            'errorMsg'   => View::errorMessage(),
        ], 'admin');
    }

    public function create(Request $req, Response $res): Response
    {
        $name         = trim((string) $req->input('name', ''));
        $slug         = trim((string) $req->input('slug', ''));
        $icon         = $this->nullIfEmpty((string) $req->input('icon', ''));
        $fieldsJson   = (string) $req->input('fields', '[]');
        $statusesJson = (string) $req->input('statuses', '[]');

        $errors = self::validate($name, $slug, $fieldsJson, $statusesJson, null);
        if ($errors !== []) {
            Session::flash('_old', compact('name', 'slug', 'icon', 'fieldsJson', 'statusesJson'));
            Session::flash('errors', $errors);
            Session::flash('error', 'Please correct the errors below.');
            return $res->redirect($this->baseUrl() . '/admin/custom-endpoints/new');
        }

        try {
            $id = CustomEndpoint::create($name, $slug, $icon, $fieldsJson, $statusesJson);
        } catch (\InvalidArgumentException $e) {
            Session::flash('_old', compact('name', 'slug', 'icon', 'fieldsJson', 'statusesJson'));
            Session::flash('error', $e->getMessage());
            return $res->redirect($this->baseUrl() . '/admin/custom-endpoints/new');
        }

        Session::flash('flash', 'Custom endpoint "' . $name . '" created.');
        return $res->redirect($this->baseUrl() . '/admin/custom-endpoints/' . $id . '/edit');
    }

    public function edit(Request $req, Response $res): Response
    {
        $id = (int) $req->param('id');
        $endpoint = CustomEndpoint::find($id);
        if ($endpoint === null) {
            return $res->error(404, 'Custom endpoint not found.');
        }
        // Surface human-friendly JSON in the textareas.
        $endpoint['fields_json_pretty']   = json_encode($endpoint['fields'],   JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $endpoint['statuses_json_pretty'] = json_encode($endpoint['statuses'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $res->view('admin.custom-endpoints.edit', [
            '__title'    => 'Edit Custom Endpoint',
            'baseUrl'    => $this->baseUrl(),
            'mode'       => 'edit',
            'id'         => $id,
            'endpoint'   => $endpoint,
            'action'     => $this->baseUrl() . '/admin/custom-endpoints/' . $id,
            'submitText' => 'Save changes',
            'csrf'       => Csrf::token(),
            'errors'     => View::errors(),
            'errorMsg'   => View::errorMessage(),
        ], 'admin');
    }

    public function update(Request $req, Response $res): Response
    {
        $id = (int) $req->param('id');
        $existing = CustomEndpoint::find($id);
        if ($existing === null) {
            return $res->error(404, 'Custom endpoint not found.');
        }

        $name         = trim((string) $req->input('name', ''));
        $slug         = trim((string) $req->input('slug', ''));
        $icon         = $this->nullIfEmpty((string) $req->input('icon', ''));
        $fieldsJson   = (string) $req->input('fields', '[]');
        $statusesJson = (string) $req->input('statuses', '[]');

        $errors = self::validate($name, $slug, $fieldsJson, $statusesJson, $id);
        if ($errors !== []) {
            Session::flash('_old', compact('name', 'slug', 'icon', 'fieldsJson', 'statusesJson'));
            Session::flash('errors', $errors);
            Session::flash('error', 'Please correct the errors below.');
            return $res->redirect($this->baseUrl() . '/admin/custom-endpoints/' . $id . '/edit');
        }

        try {
            CustomEndpoint::update($id, $name, $slug, $icon, $fieldsJson, $statusesJson);
        } catch (\InvalidArgumentException $e) {
            Session::flash('_old', compact('name', 'slug', 'icon', 'fieldsJson', 'statusesJson'));
            Session::flash('error', $e->getMessage());
            return $res->redirect($this->baseUrl() . '/admin/custom-endpoints/' . $id . '/edit');
        }

        Session::flash('flash', 'Custom endpoint "' . $name . '" updated.');
        return $res->redirect($this->baseUrl() . '/admin/custom-endpoints/' . $id . '/edit');
    }

    public function delete(Request $req, Response $res): Response
    {
        $id = (int) $req->param('id');
        $existing = CustomEndpoint::find($id);
        if ($existing === null) {
            return $res->error(404, 'Custom endpoint not found.');
        }
        CustomEndpoint::delete($id);
        Session::flash('flash', 'Custom endpoint "' . $existing['name'] . '" deleted.');
        return $res->redirect($this->baseUrl() . '/admin/custom-endpoints');
    }

    public function submissions(Request $req, Response $res): Response
    {
        $id = (int) $req->param('id');
        $endpoint = CustomEndpoint::find($id);
        if ($endpoint === null) {
            return $res->error(404, 'Custom endpoint not found.');
        }
        $filters = [
            'search' => (string) $req->query('search', ''),
        ];
        $page = max(1, (int) $req->query('page', 1));
        $result = CustomSubmission::paginate($id, $filters, $page, self::PER_PAGE);

        return $res->view('admin.custom-endpoints.submissions', [
            '__title'    => 'Submissions — ' . $endpoint['name'],
            'baseUrl'    => $this->baseUrl(),
            'endpoint'   => $endpoint,
            'filters'    => $filters,
            'page'       => $result['page'],
            'pages'      => $result['pages'],
            'total'      => $result['total'],
            'rows'       => $result['rows'],
            'perPage'    => $result['perPage'],
            'csrf'       => Csrf::token(),
            'flash'      => Session::pullFlash('flash'),
            'flashError' => Session::pullFlash('flash_error'),
        ], 'admin');
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * @return array<string, string>
     */
    private static function validate(string $name, string $slug, string $fieldsJson, string $statusesJson, ?int $excludeId): array
    {
        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Name is required.';
        } elseif (mb_strlen($name) > 255) {
            $errors['name'] = 'Name is too long (max 255).';
        }
        if ($slug === '') {
            $errors['slug'] = 'Slug is required.';
        } elseif (!preg_match('/^[a-z0-9][a-z0-9_-]{1,99}$/', $slug)) {
            $errors['slug'] = 'Slug must be lowercase letters, digits, dash, or underscore; 2-100 chars; no leading dash.';
        } else {
            $existing = CustomEndpoint::findBySlug($slug);
            if ($existing !== null && ($excludeId === null || (int) $existing['id'] !== $excludeId)) {
                $errors['slug'] = 'A custom endpoint with this slug already exists.';
            }
        }
        // Validate fields / statuses JSON.
        foreach (['fields' => $fieldsJson, 'statuses' => $statusesJson] as $name2 => $raw) {
            $trimmed = trim($raw);
            if ($trimmed === '') {
                continue;
            }
            $decoded = json_decode($trimmed, true);
            if (!is_array($decoded)) {
                $errors[$name2] = ucfirst($name2) . ' must be a JSON array.';
            } elseif (array_keys($decoded) !== range(0, count($decoded) - 1)) {
                $errors[$name2] = ucfirst($name2) . ' must be a JSON list (not an object).';
            }
        }
        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyEndpoint(): array
    {
        return [
            'id'                   => null,
            'name'                 => '',
            'slug'                 => '',
            'icon'                 => '',
            'fields'               => [],
            'statuses'             => ['pending'],
            'fields_json_pretty'   => "[\n    \n]",
            'statuses_json_pretty' => "[\n    \"pending\"\n]",
        ];
    }

    private function nullIfEmpty(string $s): ?string
    {
        $s = trim($s);
        return $s === '' ? null : $s;
    }

    private function baseUrl(): string
    {
        return rtrim((string) Config::get('BASE_URL', ''), '/');
    }
}

<?php
declare(strict_types=1);

namespace IMF\Tests\Support;

use App\Core\{Csrf, Session};
use App\Models\{Contact, CustomEndpoint, FormRoute, Registration};

trait Fixtures
{
    protected function createAdminSession(): void
    {
        Session::start();
        Session::put('_admin', [
            'id'    => 1,
            'name'  => 'Admin',
            'email' => 'admin@example.com',
            'role'  => 'super',
        ]);
    }

    protected function createCsrfToken(): string
    {
        Session::start();
        return Csrf::token();
    }

    protected function createRegistration(array $overrides = []): int
    {
        return Registration::insert(array_merge([
            'name'       => 'Fixture Person',
            'email'      => 'fixture-' . time() . '@test.com',
            'course'     => 'Fixture Course',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-08-01',
            'status'     => 'pending',
        ], $overrides));
    }

    protected function createFormRoute(int $formId = 9999, string $targetType = 'registration', ?string $slug = null): array
    {
        $stmt = \App\Core\Database::pdo()->prepare(
            'INSERT INTO form_routes (form_id, target_type, target_slug) VALUES (:fid, :tt, :slug)
             ON DUPLICATE KEY UPDATE target_type = :tt2, target_slug = :slug2'
        );
        $stmt->execute([
            ':fid'   => $formId,
            ':tt'    => $targetType,
            ':slug'  => $slug,
            ':tt2'   => $targetType,
            ':slug2' => $slug,
        ]);
        return ['form_id' => $formId, 'target_type' => $targetType, 'target_slug' => $slug];
    }

    protected function createCustomEndpoint(string $slug, array $fields = []): int
    {
        return CustomEndpoint::create(
            'Test ' . $slug,
            $slug,
            null,
            json_encode($fields),
            '["pending"]'
        );
    }

    protected function createContact(array $overrides = []): int
    {
        return Contact::insert(array_merge([
            'name'    => 'Contact Person',
            'email'   => 'contact-' . time() . '@test.com',
            'subject' => 'Test Subject',
            'message' => 'Test message body.',
        ], $overrides));
    }
}

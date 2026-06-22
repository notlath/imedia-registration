<?php

/**
 * IMedia Registration — FormRoute model.
 *
 * Maps a WordPress form_id (the IMedia Forms CPT id) to a target table
 * inside this admin app. The SubmitController looks up the route to
 * decide whether the incoming submission goes into registrations, contacts,
 * ojt, trainer, or a custom endpoint.
 *
 * Per mysql skill: form_id is the PK (BIGINT UNSIGNED) so lookups are O(1).
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class FormRoute
{
    public const TARGET_REGISTRATION = 'registration';
    public const TARGET_CONTACT      = 'contact';
    public const TARGET_OJT          = 'ojt';
    public const TARGET_TRAINER      = 'trainer';
    public const TARGET_CUSTOM       = 'custom';

    /**
     * @return array{form_id: int, target_type: string, target_slug: ?string}|null
     */
    public static function find(int $formId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT form_id, target_type, target_slug
             FROM form_routes
             WHERE form_id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $formId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            return null;
        }
        return [
            'form_id'     => (int) $row['form_id'],
            'target_type' => (string) $row['target_type'],
            'target_slug' => isset($row['target_slug']) ? (string) $row['target_slug'] : null,
        ];
    }

    /**
     * Return all registered routes (used by the future Settings page).
     *
     * @return array<int, array{form_id: int, target_type: string, target_slug: ?string}>
     */
    public static function all(): array
    {
        $stmt = Database::pdo()->query('SELECT form_id, target_type, target_slug FROM form_routes ORDER BY form_id ASC');
        $rows = $stmt ? $stmt->fetchAll() : [];
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'form_id'     => (int) $row['form_id'],
                'target_type' => (string) $row['target_type'],
                'target_slug' => isset($row['target_slug']) ? (string) $row['target_slug'] : null,
            ];
        }
        return $out;
    }

    /**
     * Insert or update a route.
     */
    public static function upsert(int $formId, string $targetType, ?string $targetSlug = null): void
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO form_routes (form_id, target_type, target_slug)
             VALUES (:id, :t, :s)
             ON DUPLICATE KEY UPDATE
               target_type = VALUES(target_type),
               target_slug = VALUES(target_slug)'
        );
        $stmt->execute([
            ':id' => $formId,
            ':t'  => $targetType,
            ':s'  => $targetSlug,
        ]);
    }

    public static function delete(int $formId): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM form_routes WHERE form_id = :id');
        $stmt->execute([':id' => $formId]);
    }
}

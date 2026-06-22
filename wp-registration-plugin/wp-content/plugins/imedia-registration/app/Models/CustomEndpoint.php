<?php

/**
 * IMedia Registration — CustomEndpoint model.
 *
 * Admin-defined dynamic submission targets. Phase 4 surface: full CRUD.
 *
 * Per php-pro: strict types, named placeholders, JSON-decode fields
 * defensively.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CustomEndpoint
{
    /**
     * @return array{
     *   id:int, name:string, slug:string, icon:?string,
     *   fields:array<int, array<string, mixed>>,
     *   statuses:array<int, string>
     * }|null
     */
    public static function findBySlug(string $slug): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name, slug, icon, fields, statuses
             FROM custom_endpoints
             WHERE slug = :slug
             LIMIT 1'
        );
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        return is_array($row) ? self::hydrate($row) : null;
    }

    /**
     * @return array{
     *   id:int, name:string, slug:string, icon:?string,
     *   fields:array<int, array<string, mixed>>,
     *   statuses:array<int, string>
     * }|null
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name, slug, icon, fields, statuses
             FROM custom_endpoints
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? self::hydrate($row) : null;
    }

    /**
     * Light shape for the index list. Includes a submissions count.
     *
     * @return array<int, array{id:int, name:string, slug:string, icon:?string, submissions_count:int}>
     */
    public static function all(): array
    {
        $stmt = Database::pdo()->query(
            'SELECT ce.id, ce.name, ce.slug, ce.icon,
                    (SELECT COUNT(*) FROM custom_submissions cs WHERE cs.endpoint_id = ce.id) AS submissions_count
             FROM custom_endpoints ce
             ORDER BY ce.id ASC'
        );
        $rows = $stmt ? $stmt->fetchAll() : [];
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id'               => (int) $row['id'],
                'name'             => (string) $row['name'],
                'slug'             => (string) $row['slug'],
                'icon'             => $row['icon'] === null ? null : (string) $row['icon'],
                'submissions_count'=> (int) $row['submissions_count'],
            ];
        }
        return $out;
    }

    /**
     * Insert a new endpoint. $fieldsJson / $statusesJson are raw JSON strings
     * from the admin (we validate by attempting to decode + re-encode).
     *
     * @return int  the new id
     */
    public static function create(string $name, string $slug, ?string $icon, string $fieldsJson, string $statusesJson): int
    {
        $fields   = self::normalizeJsonArray($fieldsJson, 'fields');
        $statuses = self::normalizeJsonArray($statusesJson, 'statuses', forceString: true);
        $stmt = Database::pdo()->prepare(
            'INSERT INTO custom_endpoints (name, slug, icon, fields, statuses)
             VALUES (:name, :slug, :icon, :fields, :statuses)'
        );
        $stmt->execute([
            ':name'     => $name,
            ':slug'     => $slug,
            ':icon'     => $icon,
            ':fields'   => json_encode($fields,   JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':statuses' => json_encode($statuses, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $name, string $slug, ?string $icon, string $fieldsJson, string $statusesJson): bool
    {
        $fields   = self::normalizeJsonArray($fieldsJson, 'fields');
        $statuses = self::normalizeJsonArray($statusesJson, 'statuses', forceString: true);
        $stmt = Database::pdo()->prepare(
            'UPDATE custom_endpoints
             SET name = :name, slug = :slug, icon = :icon,
                 fields = :fields, statuses = :statuses
             WHERE id = :id'
        );
        $stmt->execute([
            ':id'       => $id,
            ':name'     => $name,
            ':slug'     => $slug,
            ':icon'     => $icon,
            ':fields'   => json_encode($fields,   JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ':statuses' => json_encode($statuses, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(int $id): void
    {
        // FK with ON DELETE CASCADE handles submissions.
        $stmt = Database::pdo()->prepare('DELETE FROM custom_endpoints WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function hydrate(array $row): array
    {
        return [
            'id'       => (int) $row['id'],
            'name'     => (string) $row['name'],
            'slug'     => (string) $row['slug'],
            'icon'     => $row['icon'] === null ? null : (string) $row['icon'],
            'fields'   => self::decodeJsonArray($row['fields']   ?? null, 'fields'),
            'statuses' => self::decodeJsonArray($row['statuses'] ?? null, 'statuses', forceString: true),
        ];
    }

    /**
     * Validate that the supplied JSON is a JSON array. If it's a string
     * "[]" we return []; if it's "[1,2]" we return [1,2] (optionally
     * cast to string). On parse error we throw.
     *
     * @return array<int, mixed>
     */
    private static function normalizeJsonArray(string $raw, string $field, bool $forceString = false): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \InvalidArgumentException("The {$field} field must be a JSON array.");
        }
        // Ensure it's a list (json array) not an object.
        if (array_keys($decoded) !== range(0, count($decoded) - 1)) {
            throw new \InvalidArgumentException("The {$field} field must be a JSON array (not an object).");
        }
        if ($forceString) {
            return array_values(array_map(static fn ($v) => (string) $v, $decoded));
        }
        return array_values($decoded);
    }

    /**
     * @return array<int, mixed>
     */
    private static function decodeJsonArray(?string $raw, string $field, bool $forceString = false): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        if ($forceString) {
            return array_values(array_map(static fn ($v) => (string) $v, $decoded));
        }
        return $decoded;
    }
}

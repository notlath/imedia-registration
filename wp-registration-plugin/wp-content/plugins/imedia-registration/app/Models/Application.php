<?php

/**
 * IMedia Registration — Application model.
 *
 * One table for both OJT and Trainer applications, distinguished by the
 * `type` column. Phase 4 surface: full CRUD + paginate + softDelete.
 *
 * Per php-pro: strict types, named placeholders, typed return shapes.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Application
{
    public const TYPE_OJT     = 'ojt';
    public const TYPE_TRAINER = 'trainer';
    public const TYPES        = [self::TYPE_OJT, self::TYPE_TRAINER];

    public const STATUSES = ['pending', 'reviewed', 'accepted', 'rejected'];

    public static function insert(array $data): int
    {
        $type = (string) ($data['type'] ?? '');
        if (!in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException("Application::insert requires type 'ojt' or 'trainer', got: {$type}");
        }
        $stmt = Database::pdo()->prepare(
            'INSERT INTO applications
               (type, name, mobile, email, position, message,
                resume_path, resume_filename, status, remarks)
             VALUES
               (:type, :name, :mobile, :email, :position, :message,
                :rp, :rn, :status, :remarks)'
        );
        $stmt->execute([
            ':type'     => $type,
            ':name'     => $data['name']     ?? null,
            ':mobile'   => $data['mobile']   ?? null,
            ':email'    => $data['email']    ?? null,
            ':position' => $data['position'] ?? null,
            ':message'  => $data['message']  ?? null,
            ':rp'       => $data['resume_path']     ?? null,
            ':rn'       => $data['resume_filename'] ?? null,
            ':status'   => $data['status']   ?? 'pending',
            ':remarks'  => $data['remarks']  ?? null,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, type, name, mobile, email, position, message,
                    resume_path, resume_filename, status, remarks,
                    deleted_at, created_at, updated_at
             FROM applications
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return is_array($row) ? self::hydrate($row) : null;
    }

    /**
     * @param array{status?:string, search?:string} $filters
     * @return array{rows: array<int, array<string, mixed>>, total: int, page: int, perPage: int, pages: int}
     */
    public static function paginate(string $type, array $filters, int $page = 1, int $perPage = 25): array
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException("Invalid application type: {$type}");
        }
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $where   = ['type = :type', 'deleted_at IS NULL'];
        $bind    = [':type' => $type];

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $where[]         = 'status = :status';
            $bind[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where[]     = '(name LIKE :s1 OR email LIKE :s2 OR position LIKE :s3 OR mobile LIKE :s4)';
            $like        = '%' . $filters['search'] . '%';
            $bind[':s1'] = $like;
            $bind[':s2'] = $like;
            $bind[':s3'] = $like;
            $bind[':s4'] = $like;
        }

        $whereSql = implode(' AND ', $where);
        $countStmt = Database::pdo()->prepare("SELECT COUNT(*) FROM applications WHERE {$whereSql}");
        $countStmt->execute($bind);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $listStmt = Database::pdo()->prepare(
            "SELECT id, type, name, mobile, email, position, message,
                    resume_path, resume_filename, status, remarks,
                    deleted_at, created_at, updated_at
             FROM applications
             WHERE {$whereSql}
             ORDER BY id DESC
             LIMIT :lim OFFSET :off"
        );
        foreach ($bind as $k => $v) {
            $listStmt->bindValue($k, $v);
        }
        $listStmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
        $listStmt->bindValue(':off', $offset, \PDO::PARAM_INT);
        $listStmt->execute();
        $rows = array_map([self::class, 'hydrate'], $listStmt->fetchAll());

        return [
            'rows'    => $rows,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'pages'   => $total === 0 ? 0 : (int) ceil($total / $perPage),
        ];
    }

    public static function softDelete(int $id): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE applications SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $id]);
    }

    /**
     * Stream all applications of a given type, for the CSV export.
     *
     * @return \Generator<int, array<string, mixed>>
     */
    public static function allByType(string $type): \Generator
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, type, name, mobile, email, position, message,
                    resume_filename, status, remarks, created_at
             FROM applications
             WHERE type = :t AND deleted_at IS NULL
             ORDER BY id ASC'
        );
        $stmt->execute([':t' => $type]);
        while (($row = $stmt->fetch()) !== false) {
            yield self::hydrate($row);
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function hydrate(array $row): array
    {
        return [
            'id'              => (int) $row['id'],
            'type'            => (string) $row['type'],
            'name'            => $row['name']     === null ? null : (string) $row['name'],
            'mobile'          => $row['mobile']   === null ? null : (string) $row['mobile'],
            'email'           => $row['email']    === null ? null : (string) $row['email'],
            'position'        => $row['position'] === null ? null : (string) $row['position'],
            'message'         => $row['message']  === null ? null : (string) $row['message'],
            'resume_path'     => $row['resume_path']     === null ? null : (string) $row['resume_path'],
            'resume_filename' => $row['resume_filename'] === null ? null : (string) $row['resume_filename'],
            'status'          => (string) $row['status'],
            'remarks'         => $row['remarks']  === null ? null : (string) $row['remarks'],
            'deleted_at'      => $row['deleted_at'] === null ? null : (string) $row['deleted_at'],
            'created_at'      => (string) $row['created_at'],
            'updated_at'      => (string) $row['updated_at'],
        ];
    }
}

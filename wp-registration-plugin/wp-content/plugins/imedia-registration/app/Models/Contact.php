<?php

/**
 * IMedia Registration — Contact model.
 *
 * Phase 3: insert + `all()` (for CSV). Phase 4: full CRUD surface
 * (find, paginate, softDelete, allForExport).
 *
 * Per php-pro: strict types, named placeholders, typed return shapes.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Contact
{
    public const STATUSES = ['pending', 'contacted', 'resolved'];

    public static function insert(array $data): int
    {
        $map = [
            'name'    => $data['name']    ?? null,
            'mobile'  => $data['mobile']  ?? null,
            'email'   => $data['email']   ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'] ?? null,
            'status'  => $data['status']  ?? 'pending',
            'remarks' => $data['remarks'] ?? null,
        ];
        $stmt = Database::pdo()->prepare(
            'INSERT INTO contacts (name, mobile, email, subject, message, status, remarks)
             VALUES (:name, :mobile, :email, :subject, :message, :status, :remarks)'
        );
        $stmt->execute([
            ':name'    => $map['name'],
            ':mobile'  => $map['mobile'],
            ':email'   => $map['email'],
            ':subject' => $map['subject'],
            ':message' => $map['message'],
            ':status'  => $map['status'],
            ':remarks' => $map['remarks'],
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * @param array{status?:string, search?:string} $filters
     * @return array{rows: array<int, array<string, mixed>>, total: int, page: int, perPage: int, pages: int}
     */
    public static function paginate(array $filters, int $page = 1, int $perPage = 25): array
    {
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $where   = ['deleted_at IS NULL'];
        $bind    = [];

        if (!empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $where[]         = 'status = :status';
            $bind[':status'] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where[]     = '(name LIKE :s1 OR email LIKE :s2 OR subject LIKE :s3 OR mobile LIKE :s4)';
            $like        = '%' . $filters['search'] . '%';
            $bind[':s1'] = $like;
            $bind[':s2'] = $like;
            $bind[':s3'] = $like;
            $bind[':s4'] = $like;
        }

        $whereSql = implode(' AND ', $where);
        $countStmt = Database::pdo()->prepare("SELECT COUNT(*) FROM contacts WHERE {$whereSql}");
        $countStmt->execute($bind);
        $total = (int) $countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $listStmt = Database::pdo()->prepare(
            "SELECT id, name, mobile, email, subject, message, status, remarks,
                    deleted_at, created_at, updated_at
             FROM contacts
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
            'UPDATE contacts SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute([':id' => $id]);
    }

    /**
     * @return \Generator<int, array<string, mixed>>
     */
    public static function all(): \Generator
    {
        $stmt = Database::pdo()->query(
            'SELECT id, name, mobile, email, subject, message, status, remarks, created_at
             FROM contacts
             WHERE deleted_at IS NULL
             ORDER BY id ASC'
        );
        if ($stmt === false) {
            return;
        }
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
            'id'         => (int) $row['id'],
            'name'       => $row['name']     === null ? null : (string) $row['name'],
            'mobile'     => $row['mobile']   === null ? null : (string) $row['mobile'],
            'email'      => $row['email']    === null ? null : (string) $row['email'],
            'subject'    => $row['subject']  === null ? null : (string) $row['subject'],
            'message'    => $row['message']  === null ? null : (string) $row['message'],
            'status'     => (string) $row['status'],
            'remarks'    => $row['remarks']  === null ? null : (string) $row['remarks'],
            'deleted_at' => $row['deleted_at'] === null ? null : (string) $row['deleted_at'],
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }
}

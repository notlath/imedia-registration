<?php

/**
 * IMedia Registration — CustomSubmission model.
 *
 * The data column is JSON; we round-trip the array as-is. Phase 4
 * surface: paginate + softDelete + find.
 *
 * Per php-pro: strict types, named placeholders.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class CustomSubmission {
    public static function insert( int $endpointId, array $data, string $status = 'pending' ): int {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO custom_submissions (endpoint_id, data, status)
             VALUES (:eid, :data, :status)'
        );
        $stmt->execute(
            array(
                ':eid'    => $endpointId,
                ':data'   => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ':status' => $status,
            )
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * @param array{search?:string} $filters
     * @return array{rows: array<int, array<string, mixed>>, total: int, page: int, perPage: int, pages: int}
     */
    public static function paginate( int $endpointId, array $filters, int $page = 1, int $perPage = 25 ): array {
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $where   = array( 'endpoint_id = :eid', 'deleted_at IS NULL' );
        $bind    = array( ':eid' => $endpointId );

        if (! empty($filters['search'])) {
            // MySQL JSON column LIKE: cast to CHAR for simple substring search.
            $where[]      = 'CAST(data AS CHAR) LIKE :s';
            $bind[':s']   = '%' . $filters['search'] . '%';
        }

        $whereSql    = implode(' AND ', $where);
        $countStmt   = Database::pdo()->prepare("SELECT COUNT(*) FROM custom_submissions WHERE {$whereSql}");
        $countStmt->execute($bind);
        $total       = (int) $countStmt->fetchColumn();

        $offset = ( $page - 1 ) * $perPage;
        $listStmt = Database::pdo()->prepare(
            "SELECT id, endpoint_id, data, status, remarks,
                    deleted_at, created_at, updated_at
             FROM custom_submissions
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
        $rows = array_map(array( self::class, 'hydrate' ), $listStmt->fetchAll());

        return array(
            'rows'    => $rows,
            'total'   => $total,
            'page'    => $page,
            'perPage' => $perPage,
            'pages'   => $total === 0 ? 0 : (int) ceil($total / $perPage),
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function hydrate( array $row ): array {
        $data = array();
        if (! empty($row['data'])) {
            $decoded = json_decode((string) $row['data'], true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }
        return array(
            'id'          => (int) $row['id'],
            'endpoint_id' => (int) $row['endpoint_id'],
            'data'        => $data,
            'status'      => (string) $row['status'],
            'remarks'     => $row['remarks'] === null ? null : (string) $row['remarks'],
            'deleted_at'  => $row['deleted_at'] === null ? null : (string) $row['deleted_at'],
            'created_at'  => (string) $row['created_at'],
            'updated_at'  => (string) $row['updated_at'],
        );
    }
}

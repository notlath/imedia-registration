<?php

/**
 * IMedia Registration — Registration model.
 *
 * The core domain table. Phase 3 surface: find, paginate, alumni,
 * insert, update, softDelete, restore, countConfirmedForSlot,
 * thresholdSlots, stats, registrations30DaySeries, statusBreakdown,
 * topCourses, allForExport.
 *
 * Per mysql skill: every WHERE / ORDER BY column is indexed. The
 * threshold count uses the (course, start_date) composite index via
 * a literal BETWEEN date range — no functions on indexed columns.
 *
 * Per database-optimizer: SELECT COUNT(*) is paired with the same
 * WHERE as the SELECT rows so the planner reuses the index.
 *
 * Per php-pro: strict types, named placeholders, typed return shapes.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Registration {
    public const STATUSES      = array( 'pending', 'tentative', 'confirm', 'forfeit', 'reschedule' );
    public const PAYMENT_STATUSES = array( 'pending', 'deposit', 'fully_paid' );

    // -----------------------------------------------------------------
    // Reads
    // -----------------------------------------------------------------

    /**
     * @return array<string, mixed>|null  Full row, dynamic_data decoded.
     */
    public static function find( int $id ): ?array {
        $stmt = Database::pdo()->prepare(
            'SELECT id, name, mobile, email, address, course, start_date, end_date,
                    status, payment_status, paid_amount, paid_at, remark,
                    dynamic_data, resume_path, deleted_at, created_at, updated_at
             FROM registrations
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(array( ':id' => $id ));
        $row = $stmt->fetch();
        if (! is_array($row)) {
            return null;
        }
        return self::hydrate($row);
    }

    /**
     * @param array{
     *   status?:string, course?:string, search?:string,
     *   date_from?:string, date_to?:string
     * } $filters
     * @return array{rows: array<int, array<string, mixed>>, total: int, page: int, perPage: int, pages: int}
     */
    public static function paginate( array $filters, int $page = 1, int $perPage = 25 ): array {
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $where   = array( 'deleted_at IS NULL' );
        $bind    = array();

        if (! empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $where[]          = 'status = :status';
            $bind[':status']  = $filters['status'];
        }
        if (! empty($filters['course'])) {
            $where[]         = 'course = :course';
            $bind[':course'] = $filters['course'];
        }
        if (! empty($filters['search'])) {
            $where[]          = '(name LIKE :s1 OR email LIKE :s2 OR mobile LIKE :s3)';
            $like             = '%' . $filters['search'] . '%';
            $bind[':s1']      = $like;
            $bind[':s2']      = $like;
            $bind[':s3']      = $like;
        }
        if (! empty($filters['date_from']) && self::isYmd($filters['date_from'])) {
            $where[]           = 'created_at >= :df';
            $bind[':df']       = $filters['date_from'] . ' 00:00:00';
        }
        if (! empty($filters['date_to']) && self::isYmd($filters['date_to'])) {
            $where[]           = 'created_at <= :dt';
            $bind[':dt']       = $filters['date_to'] . ' 23:59:59';
        }

        $whereSql = implode(' AND ', $where);

        // Count (mysql skill: uses the same WHERE so the planner reuses
        // the indexes chosen for the rows query).
        $countSql = "SELECT COUNT(*) FROM registrations WHERE {$whereSql}";
        $countStmt = Database::pdo()->prepare($countSql);
        $countStmt->execute($bind);
        $total = (int) $countStmt->fetchColumn();

        // Rows.
        $offset = ( $page - 1 ) * $perPage;
        $listSql = "SELECT id, name, mobile, email, address, course, start_date, end_date,
                           status, payment_status, paid_amount, paid_at, remark,
                           dynamic_data, resume_path, deleted_at, created_at, updated_at
                    FROM registrations
                    WHERE {$whereSql}
                    ORDER BY id DESC
                    LIMIT :lim OFFSET :off";
        $listStmt = Database::pdo()->prepare($listSql);
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
     * Same shape as paginate() but with deleted_at IS NOT NULL.
     */
    public static function alumni( array $filters, int $page = 1, int $perPage = 25 ): array {
        $filters = $filters; // keep signature consistent
        $page    = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $where   = array( 'deleted_at IS NOT NULL' );
        $bind    = array();

        if (! empty($filters['search'])) {
            $where[]     = '(name LIKE :s1 OR email LIKE :s2 OR mobile LIKE :s3)';
            $like        = '%' . $filters['search'] . '%';
            $bind[':s1'] = $like;
            $bind[':s2'] = $like;
            $bind[':s3'] = $like;
        }

        $whereSql = implode(' AND ', $where);
        $countSql = "SELECT COUNT(*) FROM registrations WHERE {$whereSql}";
        $countStmt = Database::pdo()->prepare($countSql);
        $countStmt->execute($bind);
        $total = (int) $countStmt->fetchColumn();

        $offset = ( $page - 1 ) * $perPage;
        $listSql = "SELECT id, name, mobile, email, address, course, start_date, end_date,
                           status, payment_status, paid_amount, paid_at, remark,
                           dynamic_data, resume_path, deleted_at, created_at, updated_at
                    FROM registrations
                    WHERE {$whereSql}
                    ORDER BY deleted_at DESC, id DESC
                    LIMIT :lim OFFSET :off";
        $listStmt = Database::pdo()->prepare($listSql);
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
     * Used by the threshold checker. Composite index hit via literal range.
     *
     * @return array<int, array{course:string, course_year:int, course_month:int, count:int}>
     */
    public static function thresholdSlots( int $threshold, int $limit = 20 ): array {
        // mysql skill: literal date range, no functions on indexed columns.
        $sql = "SELECT course,
                       YEAR(start_date)  AS course_year,
                       MONTH(start_date) AS course_month,
                       COUNT(*) AS cnt
                FROM registrations
                WHERE status = 'confirm'
                  AND deleted_at IS NULL
                  AND start_date >= :d1
                GROUP BY course, course_year, course_month
                HAVING cnt >= :thr
                ORDER BY course_year DESC, course_month DESC, cnt DESC
                LIMIT :lim";
        $stmt = Database::pdo()->prepare($sql);
        $stmt->bindValue(':d1', date('Y-01-01'));  // any row in the current year or later
        $stmt->bindValue(':thr', $threshold, \PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $out = array();
        foreach ($rows as $row) {
            $out[] = array(
                'course'       => (string) $row['course'],
                'course_year'  => (int) $row['course_year'],
                'course_month' => (int) $row['course_month'],
                'count'        => (int) $row['cnt'],
            );
        }
        return $out;
    }

    /**
     * Count of confirm-status rows for a single (course, year, month) slot.
     */
    public static function countConfirmedForSlot( string $course, int $year, int $month ): int {
        // mysql skill: literal date range so the (course, start_date)
        // composite index is fully used.
        $d1 = sprintf('%04d-%02d-01', $year, $month);
        // Last day of month: use the first of next month minus 1 day, or just
        // the 31st which is always >= the actual last day.
        $d2 = sprintf('%04d-%02d-31', $year, $month);
        $stmt = Database::pdo()->prepare(
            "SELECT COUNT(*) FROM registrations
             WHERE course = :c
               AND status = 'confirm'
               AND deleted_at IS NULL
               AND start_date BETWEEN :d1 AND :d2"
        );
        $stmt->execute(
            array(
                ':c' => $course,
                ':d1' => $d1,
                ':d2' => $d2,
            )
        );
        return (int) $stmt->fetchColumn();
    }

    /**
     * KPI counts for the dashboard. All queries are bounded and indexed.
     *
     * @return array{
     *   total:int, new_today:int, new_week:int, new_month:int,
     *   by_status:array<string, int>, by_payment:array<string, int>
     * }
     */
    public static function stats(): array {
        $today = date('Y-m-d') . ' 00:00:00';
        $week  = date('Y-m-d', strtotime('-7 days')) . ' 00:00:00';
        $month = date('Y-m-01') . ' 00:00:00';

        // Single round-trip for the four headline counts. The three date
        // buckets reuse the same plan: each SUM(CASE WHEN ...) is a
        // no-op filter the optimizer folds in. Drop deleted_at IS NULL
        // into every branch so the count never includes soft-deletes.
        $stmt = Database::pdo()->prepare(
            'SELECT
                SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END)                                    AS total,
                SUM(CASE WHEN deleted_at IS NULL AND created_at >= :today THEN 1 ELSE 0 END)          AS new_today,
                SUM(CASE WHEN deleted_at IS NULL AND created_at >= :week  THEN 1 ELSE 0 END)          AS new_week,
                SUM(CASE WHEN deleted_at IS NULL AND created_at >= :month THEN 1 ELSE 0 END)          AS new_month
             FROM registrations'
        );
        $stmt->execute(
            array(
                ':today' => $today,
                ':week' => $week,
                ':month' => $month,
            )
        );
        $row = $stmt->fetch() ?: array();

        $byStatus = self::groupCount(
            'SELECT status, COUNT(*) AS c FROM registrations
             WHERE deleted_at IS NULL GROUP BY status'
        );

        $byPayment = self::groupCount(
            'SELECT payment_status AS k, COUNT(*) AS c FROM registrations
             WHERE deleted_at IS NULL GROUP BY payment_status'
        );

        return array(
            'total'      => (int) ( $row['total'] ?? 0 ),
            'new_today'  => (int) ( $row['new_today'] ?? 0 ),
            'new_week'   => (int) ( $row['new_week'] ?? 0 ),
            'new_month'  => (int) ( $row['new_month'] ?? 0 ),
            'by_status'  => $byStatus,
            'by_payment' => $byPayment,
        );
    }

    /**
     * 30-day daily registration series for the line chart.
     *
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public static function registrations30DaySeries(): array {
        $start = date('Y-m-d', strtotime('-29 days')) . ' 00:00:00';
        $stmt = Database::pdo()->prepare(
            'SELECT DATE(created_at) AS d, COUNT(*) AS c
             FROM registrations
             WHERE deleted_at IS NULL AND created_at >= :start
             GROUP BY DATE(created_at)
             ORDER BY d ASC'
        );
        $stmt->execute(array( ':start' => $start ));
        $rows = $stmt->fetchAll();

        // Fill the 30-day window so the chart is dense.
        $map = array();
        foreach ($rows as $row) {
            $map[ (string) $row['d'] ] = (int) $row['c'];
        }
        $labels = array();
        $data   = array();
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = $d;
            $data[]   = $map[ $d ] ?? 0;
        }
        return array(
            'labels' => $labels,
            'data' => $data,
        );
    }

    /**
     * Top courses by confirm count, for the bar chart.
     *
     * @return array{labels: array<int, string>, data: array<int, int>}
     */
    public static function topCoursesByConfirm( int $limit = 5 ): array {
        $stmt = Database::pdo()->prepare(
            "SELECT course, COUNT(*) AS c
             FROM registrations
             WHERE status = 'confirm' AND deleted_at IS NULL
             GROUP BY course
             ORDER BY c DESC
             LIMIT :lim"
        );
        $stmt->bindValue(':lim', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        $labels = array();
        $data   = array();
        foreach ($rows as $row) {
            $labels[] = (string) $row['course'];
            $data[]   = (int) $row['c'];
        }
        return array(
            'labels' => $labels,
            'data' => $data,
        );
    }

    /**
     * Stream all rows for the CSV export.
     *
     * @param array{status?:string, course?:string} $filters
     * @return \Generator<int, array<string, mixed>>
     */
    public static function allForExport( array $filters = array() ): \Generator {
        $where = array( 'deleted_at IS NULL' );
        $bind  = array();
        if (! empty($filters['status']) && in_array($filters['status'], self::STATUSES, true)) {
            $where[]         = 'status = :status';
            $bind[':status'] = $filters['status'];
        }
        if (! empty($filters['course'])) {
            $where[]         = 'course = :course';
            $bind[':course'] = $filters['course'];
        }
        $whereSql = implode(' AND ', $where);
        // Unbuffered query for memory efficiency.
        $stmt = Database::pdo()->prepare(
            "SELECT id, name, mobile, email, address, course, start_date, end_date,
                    status, payment_status, paid_amount, paid_at, remark,
                    dynamic_data, created_at, updated_at
             FROM registrations
             WHERE {$whereSql}
             ORDER BY id ASC"
        );
        $stmt->execute($bind);
        while (( $row = $stmt->fetch() ) !== false) {
            yield self::hydrate($row);
        }
    }

    // -----------------------------------------------------------------
    // Writes
    // -----------------------------------------------------------------

    /**
     * @param array<string, mixed> $data Whitelisted keys, plus `dynamic_data` (array).
     */
    public static function insert( array $data ): int {
        $dyn = $data['dynamic_data'] ?? null;
        if (is_array($dyn)) {
            $dyn = json_encode($dyn, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } elseif ($dyn !== null && ! is_string($dyn)) {
            $dyn = null;
        }
        $stmt = Database::pdo()->prepare(
            'INSERT INTO registrations
               (name, mobile, email, address, course, start_date, end_date,
                status, payment_status, paid_amount, paid_at, remark, dynamic_data)
             VALUES
               (:name, :mobile, :email, :address, :course, :start_date, :end_date,
                :status, :payment_status, :paid_amount, :paid_at, :remark, :dynamic_data)'
        );
        $stmt->execute(
            array(
                ':name'            => (string) ( $data['name'] ?? '' ),
                ':mobile'          => self::nullIfEmpty($data['mobile'] ?? null),
                ':email'           => (string) ( $data['email'] ?? '' ),
                ':address'         => self::nullIfEmpty($data['address'] ?? null),
                ':course'          => (string) ( $data['course'] ?? '' ),
                ':start_date'      => self::toYmd($data['start_date'] ?? null),
                ':end_date'        => self::toYmd($data['end_date'] ?? null),
                ':status'          => self::enum($data['status'] ?? 'pending', self::STATUSES, 'pending'),
                ':payment_status'  => self::enum($data['payment_status'] ?? 'pending', self::PAYMENT_STATUSES, 'pending'),
                ':paid_amount'     => self::nullIfEmpty($data['paid_amount'] ?? null),
                ':paid_at'         => self::toYmd($data['paid_at'] ?? null),
                ':remark'          => self::nullIfEmpty($data['remark'] ?? null),
                ':dynamic_data'    => $dyn,
            )
        );
        return (int) Database::pdo()->lastInsertId();
    }

    /**
     * Whitelisted update. Returns the previous row for status-change diffs.
     *
     * @param array<string, mixed> $data
     * @return array{before:?array, after:?array, changed: bool, prev_status:?string, new_status:?string}
     */
    public static function update( int $id, array $data ): array {
        $before = self::find($id);
        if ($before === null) {
            return array(
                'before' => null,
                'after' => null,
                'changed' => false,
                'prev_status' => null,
                'new_status' => null,
            );
        }

        $set = array();
        $bind = array( ':id' => $id );
        $editable = array(
            'name',
            'mobile',
            'email',
            'address',
            'course',
            'start_date',
            'end_date',
            'status',
            'payment_status',
            'paid_amount',
            'paid_at',
            'remark',
        );
        foreach ($editable as $col) {
            if (! array_key_exists($col, $data)) {
                continue;
            }
            $set[] = "`{$col}` = :{$col}";
            $bind[ ":{$col}" ] = match ($col) {
                'status'         => self::enum($data[ $col ] ?? null, self::STATUSES, $before['status']),
                'payment_status' => self::enum($data[ $col ] ?? null, self::PAYMENT_STATUSES, $before['payment_status']),
                'paid_amount'    => self::nullIfEmpty($data[ $col ] ?? null),
                'paid_at'        => self::toYmd($data[ $col ] ?? null),
                'start_date', 'end_date' => self::toYmd($data[ $col ] ?? null),
                'mobile', 'address', 'remark' => self::nullIfEmpty($data[ $col ] ?? null),
                default => (string) ( $data[ $col ] ?? '' ),
            };
        }
        if (array_key_exists('dynamic_data', $data)) {
            $dyn = $data['dynamic_data'];
            if (is_array($dyn)) {
                $dyn = json_encode($dyn, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            $set[] = '`dynamic_data` = :dynamic_data';
            $bind[':dynamic_data'] = is_string($dyn) ? $dyn : null;
        }

        if ($set === array()) {
            return array(
                'before' => $before,
                'after' => $before,
                'changed' => false,
                'prev_status' => $before['status'],
                'new_status' => $before['status'],
            );
        }

        $sql = 'UPDATE registrations SET ' . implode(', ', $set) . ' WHERE id = :id';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($bind);

        $after = self::find($id);
        return array(
            'before'     => $before,
            'after'      => $after,
            'changed'    => true,
            'prev_status' => $before['status'],
            'new_status' => $after['status'] ?? null,
        );
    }

    public static function softDelete( int $id ): void {
        $stmt = Database::pdo()->prepare(
            'UPDATE registrations SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute(array( ':id' => $id ));
    }

    public static function restore( int $id, string $newStatus ): void {
        $stmt = Database::pdo()->prepare(
            'UPDATE registrations
             SET deleted_at = NULL, status = :s
             WHERE id = :id'
        );
        $stmt->execute(
            array(
                ':s' => $newStatus,
                ':id' => $id,
            )
        );
    }

    /**
     * @return array<int, string>
     */
    public static function distinctCourses(): array {
        $stmt = Database::pdo()->query(
            'SELECT DISTINCT course FROM registrations
             WHERE deleted_at IS NULL
             ORDER BY course ASC'
        );
        if ($stmt === false) {
            return array();
        }
        $rows = $stmt->fetchAll();
        $out = array();
        foreach ($rows as $row) {
            $out[] = (string) $row['course'];
        }
        return $out;
    }

    /**
     * Phase 5: set the resume_path after a successful upload.
     */
    public static function setResumePath( int $id, string $relativePath ): void {
        $stmt = Database::pdo()->prepare(
            'UPDATE registrations SET resume_path = :p WHERE id = :id'
        );
        $stmt->execute(
            array(
                ':p' => $relativePath,
                ':id' => $id,
            )
        );
    }

    /**
     * Phase 5: get the resume_path for the download endpoint.
     * Returns null if the registration doesn't exist or has no resume.
     */
    public static function getResumePath( int $id ): ?string {
        $stmt = Database::pdo()->prepare(
            'SELECT resume_path FROM registrations WHERE id = :id LIMIT 1'
        );
        $stmt->execute(array( ':id' => $id ));
        $row = $stmt->fetch();
        if (! is_array($row)) {
            return null;
        }
        $p = $row['resume_path'] ?? null;
        return is_string($p) && $p !== '' ? $p : null;
    }

    /**
     * Bulk-update status for many ids (single transaction). Returns the count
     * of rows actually updated.
     */
    public static function bulkUpdateStatus( array $ids, string $newStatus ): int {
        if ($ids === array()) {
            return 0;
        }
        $newStatus = self::enum($newStatus, self::STATUSES, 'pending');
        $placeholders = array();
        $bind = array( ':s' => $newStatus );
        foreach (array_values($ids) as $i => $id) {
            $key = ":id{$i}";
            $placeholders[] = $key;
            $bind[ $key ] = (int) $id;
        }
        $sql = 'UPDATE registrations SET status = :s WHERE id IN (' . implode(',', $placeholders) . ')';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($bind);
        return $stmt->rowCount();
    }

    // -----------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private static function hydrate( array $row ): array {
        // Decode dynamic_data JSON if present.
        $dyn = array();
        if (! empty($row['dynamic_data'])) {
            $decoded = json_decode((string) $row['dynamic_data'], true);
            if (is_array($decoded)) {
                $dyn = $decoded;
            }
        }
        return array(
            'id'             => (int) $row['id'],
            'name'           => (string) $row['name'],
            'mobile'         => $row['mobile'] === null ? null : (string) $row['mobile'],
            'email'          => (string) $row['email'],
            'address'        => $row['address'] === null ? null : (string) $row['address'],
            'course'         => (string) $row['course'],
            'start_date'     => (string) $row['start_date'],
            'end_date'       => (string) $row['end_date'],
            'status'         => (string) $row['status'],
            'payment_status' => (string) $row['payment_status'],
            'paid_amount'    => $row['paid_amount'] === null ? null : (string) $row['paid_amount'],
            'paid_at'        => $row['paid_at'] === null ? null : (string) $row['paid_at'],
            'remark'         => $row['remark'] === null ? null : (string) $row['remark'],
            'dynamic_data'   => $dyn,
            'resume_path'    => isset($row['resume_path']) && $row['resume_path'] !== null ? (string) $row['resume_path'] : null,
            'deleted_at'     => $row['deleted_at'] === null ? null : (string) $row['deleted_at'],
            'created_at'     => (string) $row['created_at'],
            'updated_at'     => (string) $row['updated_at'],
        );
    }

    /**
     * @return array<string, int>
     */
    private static function groupCount( string $sql ): array {
        $rows = Database::pdo()->query($sql)->fetchAll();
        $out = array();
        foreach ($rows as $row) {
            // Detect which column holds the key. The first SELECT uses 'status',
            // the second aliases to 'k'. Be explicit.
            $key = $row['status'] ?? $row['k'] ?? null;
            if ($key === null) {
                continue;
            }
            $out[ (string) $key ] = (int) $row['c'];
        }
        return $out;
    }

    private static function nullIfEmpty( mixed $v ): mixed {
        if ($v === null) {
            return null;
        }
        $s = is_scalar($v) ? (string) $v : '';
        return $s === '' ? null : ( is_string($v) ? $v : $s );
    }

    private static function toYmd( mixed $v ): ?string {
        if ($v === null || $v === '') {
            return null;
        }
        $s = (string) $v;
        if (! self::isYmd($s)) {
            return null;
        }
        return $s;
    }

    private static function isYmd( string $s ): bool {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
    }

    /**
     * @param array<int, string> $allowed
     */
    private static function enum( mixed $v, array $allowed, string $default ): string {
        $s = is_scalar($v) ? (string) $v : '';
        return in_array($s, $allowed, true) ? $s : $default;
    }
}

<?php

/**
 * IMedia Registration — StatusHistory model.
 *
 * Every status / payment_status change to any tracked entity is logged
 * here. Powers the per-row "View history" view and the audit trail.
 *
 * Per php-pro: strict types, named placeholders, typed arrays.
 */

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class StatusHistory
{
    public const ENTITY_REGISTRATION       = 'registration';
    public const ENTITY_CONTACT            = 'contact';
    public const ENTITY_APPLICATION        = 'application';
    public const ENTITY_CUSTOM_SUBMISSION  = 'custom_submission';

    public const FIELD_STATUS        = 'status';
    public const FIELD_PAYMENT_STATUS = 'payment_status';

    public static function log(
        string $entityType,
        int $entityId,
        string $field,
        ?string $oldValue,
        string $newValue,
        ?int $changedBy = null,
        ?string $note = null
    ): void {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO status_history
               (entity_type, entity_id, field, old_value, new_value, changed_by, note)
             VALUES
               (:et, :eid, :f, :ov, :nv, :cb, :n)'
        );
        $stmt->execute([
            ':et'  => $entityType,
            ':eid' => $entityId,
            ':f'   => $field,
            ':ov'  => $oldValue,
            ':nv'  => $newValue,
            ':cb'  => $changedBy,
            ':n'   => $note,
        ]);
    }

    /**
     * @return array<int, array{
     *   id:int, entity_type:string, entity_id:int, field:string,
     *   old_value:?string, new_value:string, changed_by:?int, changed_at:string, note:?string
     * }>
     */
    public static function forEntity(string $entityType, int $entityId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT id, entity_type, entity_id, field, old_value, new_value, changed_by, changed_at, note
             FROM status_history
             WHERE entity_type = :et AND entity_id = :eid
             ORDER BY changed_at DESC, id DESC'
        );
        $stmt->execute([':et' => $entityType, ':eid' => $entityId]);
        $rows = $stmt->fetchAll();
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id'          => (int) $row['id'],
                'entity_type' => (string) $row['entity_type'],
                'entity_id'   => (int) $row['entity_id'],
                'field'       => (string) $row['field'],
                'old_value'   => $row['old_value'] === null ? null : (string) $row['old_value'],
                'new_value'   => (string) $row['new_value'],
                'changed_by'  => $row['changed_by'] === null ? null : (int) $row['changed_by'],
                'changed_at'  => (string) $row['changed_at'],
                'note'        => $row['note'] === null ? null : (string) $row['note'],
            ];
        }
        return $out;
    }
}

<?php

/**
 * IMedia Registration — StatusLogger service.
 *
 * Wraps StatusHistory::log() with sensible defaults: if $changedBy is
 * null, we fill in Auth::id() so the audit trail is always attributed.
 *
 * Per php-pro: strict types, final class, static methods.
 */

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Models\StatusHistory;

final class StatusLogger {
    public static function log(
        string $entityType,
        int $entityId,
        string $field,
        ?string $oldValue,
        string $newValue,
        ?int $changedBy = null,
        ?string $note = null
    ): void {
        $changedBy ??= Auth::id();
        StatusHistory::log($entityType, $entityId, $field, $oldValue, $newValue, $changedBy, $note);
    }
}

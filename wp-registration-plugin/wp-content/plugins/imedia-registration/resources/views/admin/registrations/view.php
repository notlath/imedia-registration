<?php
/**
 * Read-only registration view.
 *
 * @var array  $row
 * @var array  $history
 * @var string $baseUrl
 * @var ?string $flash
 */
$row     = is_array($row ?? null) ? $row : [];
$history = is_array($history ?? null) ? $history : [];
$baseUrl = (string) ($baseUrl ?? '');
$id      = (int) ($row['id'] ?? 0);
?>
<?php if (is_string($flash ?? null) && $flash !== ''): ?>
    <div class="imreg-flash imreg-flash--success" role="status" aria-live="polite"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="imreg-flex imreg-justify-between imreg-items-center imreg-mb-4 imreg-gap-3" style="flex-wrap:wrap;">
    <div>
        <h2 class="imreg-text-display" style="font-size:1.5rem;font-weight:700;margin:0 0 0.25rem;letter-spacing:-0.01em;">Registration #<?= (int) $id ?></h2>
        <div class="imreg-text-muted" style="font-size:0.8125rem;">
            Created <?= htmlspecialchars((string) ($row['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            <?php if (!empty($row['updated_at']) && $row['updated_at'] !== $row['created_at']): ?>
                &middot; Updated <?= htmlspecialchars((string) $row['updated_at'], ENT_QUOTES, 'UTF-8') ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="imreg-flex imreg-gap-2">
        <a href="<?= htmlspecialchars($baseUrl . '/admin/registrations/' . $id . '/edit', ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--primary">Edit</a>
        <form method="post" action="<?= htmlspecialchars($baseUrl . '/admin/registrations/' . $id . '/delete', ENT_QUOTES, 'UTF-8') ?>" data-imreg-confirm="Soft-delete this registration? It will move to Alumni." style="display:inline;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="imreg-btn imreg-btn--danger">Delete</button>
        </form>
    </div>
</div>

<?php
$statusMap = [
    'pending'    => ['pending',    'Pending'],
    'tentative'  => ['tentative',  'Tentative'],
    'confirm'    => ['confirm',    'Confirm'],
    'forfeit'    => ['forfeit',    'Forfeit'],
    'reschedule' => ['reschedule', 'Reschedule'],
];
$payMap = [
    'pending'    => ['',          'Pending'],
    'deposit'    => ['pending',   'Deposit'],
    'fully_paid' => ['confirm',   'Fully paid'],
];
$curStatus = (string) ($row['status'] ?? '');
$curPay    = (string) ($row['payment_status'] ?? '');
[$sMod, $sLabel] = $statusMap[$curStatus] ?? ['', ucfirst($curStatus)];
[$pMod, $pLabel] = $payMap[$curPay] ?? ['', ucfirst(str_replace('_', ' ', $curPay))];
?>
<div class="imreg-grid--form imreg-mb-6 imreg-card">
    <div>
        <div class="imreg-text-muted" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.04em;">Name</div>
        <div style="font-size:0.9375rem;margin-top:0.125rem;"><?= htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div>
        <div class="imreg-text-muted" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.04em;">Email</div>
        <div style="font-size:0.9375rem;margin-top:0.125rem;"><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div>
        <div class="imreg-text-muted" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.04em;">Mobile</div>
        <div style="font-size:0.9375rem;margin-top:0.125rem;"><?= htmlspecialchars((string) ($row['mobile'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div>
        <div class="imreg-text-muted" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.04em;">Course</div>
        <div style="font-size:0.9375rem;margin-top:0.125rem;"><?= htmlspecialchars((string) ($row['course'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div>
        <div class="imreg-text-muted" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.04em;">Start date</div>
        <div style="font-size:0.9375rem;margin-top:0.125rem;"><?= htmlspecialchars((string) ($row['start_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div>
        <div class="imreg-text-muted" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.04em;">End date</div>
        <div style="font-size:0.9375rem;margin-top:0.125rem;"><?= htmlspecialchars((string) ($row['end_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div>
        <div class="imreg-text-muted" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.04em;">Status</div>
        <div style="margin-top:0.25rem;">
            <span class="imreg-badge<?= $sMod !== '' ? ' imreg-badge--' . $sMod : '' ?>"><span class="imreg-sr-only">Status:</span><?= htmlspecialchars($sLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
    <div>
        <div class="imreg-text-muted" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.04em;">Payment status</div>
        <div style="margin-top:0.25rem;">
            <span class="imreg-badge<?= $pMod !== '' ? ' imreg-badge--' . $pMod : '' ?>"><span class="imreg-sr-only">Payment status:</span><?= htmlspecialchars($pLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
    <div>
        <div class="imreg-text-muted" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.04em;">Paid amount</div>
        <div class="imreg-text-tabular" style="font-size:0.9375rem;margin-top:0.125rem;"><?= htmlspecialchars((string) ($row['paid_amount'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div>
        <div class="imreg-text-muted" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.04em;">Paid date</div>
        <div style="font-size:0.9375rem;margin-top:0.125rem;"><?= htmlspecialchars((string) ($row['paid_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <div class="imreg-field--full">
        <div class="imreg-text-muted" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.04em;">Remark</div>
        <div style="font-size:0.9375rem;margin-top:0.125rem;"><?= htmlspecialchars((string) ($row['remark'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <?php if (!empty($row['resume_path'])): ?>
        <div class="imreg-field--full" style="margin-top:0.5rem;padding-top:0.75rem;border-top:1px solid var(--color-outline-variant);">
            <div class="imreg-text-muted" style="font-size:0.6875rem;text-transform:uppercase;letter-spacing:0.04em;">Resume</div>
            <div style="margin-top:0.5rem;display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                <a href="<?= htmlspecialchars($baseUrl . '/admin/registrations/' . $id . '/resume', ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="imreg-btn imreg-btn--primary imreg-btn--sm">Download resume</a>
                <span class="imreg-text-mono imreg-text-muted" style="font-size:0.75rem;word-break:break-all;"><?= htmlspecialchars((string) $row['resume_path'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($row['dynamic_data']) && is_array($row['dynamic_data'])): ?>
    <h3 class="imreg-text-display" style="font-size:1rem;font-weight:600;margin:0 0 0.5rem;color:var(--color-fg);">dynamic_data</h3>
    <pre class="imreg-text-mono" style="background:var(--color-surface-container-low);color:var(--color-fg);padding:0.75rem;border-radius:var(--radius-md);border:1px solid var(--color-outline-variant);overflow-x:auto;font-size:0.75rem;line-height:1.5;margin:0 0 1.5rem;"><?= htmlspecialchars(json_encode($row['dynamic_data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></pre>
<?php endif; ?>

<h3 class="imreg-text-display" style="font-size:1rem;font-weight:600;margin:1.5rem 0 0.5rem;color:var(--color-fg);">Status history</h3>
<?php if ($history === []): ?>
    <p class="imreg-text-muted">No history yet.</p>
<?php else:
    $columns = ['When', 'Field', 'From', 'To', 'Note'];
    $rowKeys = ['changed_at', 'field', 'old_value', 'new_value', 'note'];
    $rowsTbl = array_map(static function ($h) {
        return [
            'changed_at' => (string) $h['changed_at'],
            'field'      => (string) $h['field'],
            'old_value'  => (string) ($h['old_value'] ?? '—'),
            'new_value'  => (string) $h['new_value'],
            'note'       => (string) ($h['note'] ?? ''),
        ];
    }, $history);
    $empty = 'No history yet.';
    include IMREG_VIEWS_PATH . '/partials/table.php';
endif; ?>

<?php
/**
 * Threshold alerts view.
 *
 * @var string  $baseUrl
 * @var int     $threshold
 * @var array   $slots
 * @var array   $sent
 * @var ?string $flash
 */
$baseUrl   = (string) ($baseUrl ?? '');
$threshold = (int) ($threshold ?? 9);
$slots     = is_array($slots ?? null) ? $slots : [];
$sent      = is_array($sent ?? null) ? $sent : [];
$monthNames = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
               7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
?>
<?php if (is_string($flash ?? null) && $flash !== ''): ?>
    <div class="imreg-flash imreg-flash--success" role="status" aria-live="polite"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="imreg-card imreg-form-narrow imreg-mb-6">
    <div class="imreg-readout__label">Current threshold (from <a href="<?= htmlspecialchars($baseUrl . '/admin/settings', ENT_QUOTES, 'UTF-8') ?>">Settings</a>)</div>
    <div class="imreg-figure-md imreg-mt-2"><?= $threshold ?></div>
    <div class="imreg-hint">Confirmed students per (course, year, month) before the alert fires.</div>
</div>

<h2 class="imreg-heading-md">At or over threshold</h2>
<?php if ($slots === []): ?>
    <p class="imreg-text-muted">No slots are at or over the threshold right now. As soon as the <?= $threshold ?><sup>th</sup> student confirms for a (course, year, month), it will appear here.</p>
<?php else:
    $columns = ['Course', 'Year', 'Month', 'Confirmed', 'Threshold'];
    $rowKeys = ['course', 'course_year', 'course_month', 'count', '__threshold'];
    $rows    = array_map(static function (array $s) use ($monthNames, $threshold): array {
        return [
            'course'       => $s['course'],
            'course_year'  => (int) $s['course_year'],
            'course_month' => $monthNames[(int) $s['course_month']] ?? '',
            'count'        => (int) $s['count'],
            '__threshold'  => $threshold,
        ];
    }, $slots);
    $cellCb = [
        null,
        null,
        null,
        null,
        static fn ($row, $key) => '<span class="imreg-text-tabular">' . (int) $row['__threshold'] . '</span>',
    ];
    $caption = 'Course slots at or over the alert threshold';
    $empty = 'No slots are at or over the threshold.';
    include IMREG_VIEWS_PATH . '/partials/table.php';
endif; ?>

<h2 class="imreg-heading-md imreg-mt-10">Recent alert emails (queued)</h2>
<?php if ($sent === []): ?>
    <p class="imreg-text-muted">No alerts have been queued yet.</p>
<?php else:
    $columns = ['Course', 'Year', 'Month', 'Queued at'];
    $rowKeys = ['course', 'course_year', 'course_month', 'sent_at'];
    $rows    = array_map(static function (array $s) use ($monthNames): array {
        return [
            'course'       => $s['course'],
            'course_year'  => (int) $s['course_year'],
            'course_month' => $monthNames[(int) $s['course_month']] ?? '',
            'sent_at'      => (string) $s['sent_at'],
        ];
    }, $sent);
    $caption = 'Recently queued alert emails';
    $empty = 'No alert emails queued yet.';
    include IMREG_VIEWS_PATH . '/partials/table.php';
endif; ?>

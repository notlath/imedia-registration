<?php
/**
 * Outbox view — 3 tabs (Queued, Sent, Failed) with a Process button.
 *
 * @var string  $tab
 * @var array   $tabs
 * @var array   $rows
 * @var array   $counts
 * @var string  $csrf
 * @var string  $baseUrl
 * @var ?string $flash
 * @var ?string $flashErr
 */
$tab      = (string) ($tab ?? 'queued');
$tabs     = is_array($tabs ?? null) ? $tabs : ['queued', 'sent', 'failed'];
$rows     = is_array($rows ?? null) ? $rows : [];
$counts   = is_array($counts ?? null) ? $counts : ['queued' => 0, 'sent' => 0, 'failed' => 0];
$csrf     = (string) ($csrf ?? '');
$baseUrl  = (string) ($baseUrl ?? '');
$flash    = $flash ?? null;
$flashErr = $flashErr ?? null;
?>
<?php if (is_string($flash) && $flash !== ''): ?>
    <div class="imreg-flash imreg-flash--success" role="status" aria-live="polite"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (is_string($flashErr) && $flashErr !== ''): ?>
    <div class="imreg-flash imreg-flash--error" role="alert" aria-live="assertive"><?= htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<nav class="imreg-tabs" aria-label="Outbox tabs">
    <?php foreach ($tabs as $t):
        $isActive = $t === $tab;
        $count    = (int) ($counts[$t] ?? 0);
        $labelMap = ['queued' => 'Queued', 'sent' => 'Sent', 'failed' => 'Failed'];
        $label    = $labelMap[$t] ?? ucfirst($t);
    ?>
        <a class="imreg-tab" href="<?= htmlspecialchars($baseUrl . '/admin/outbox?tab=' . $t, ENT_QUOTES, 'UTF-8') ?>"
           <?= $isActive ? 'aria-current="page"' : '' ?>>
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            <span class="imreg-tab__badge"><?= $count ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($tab === 'queued'): ?>
    <div class="imreg-card imreg-mb-6">
        <div class="imreg-flex imreg-justify-between imreg-items-center imreg-gap-3 imreg-flex-wrap">
            <div>
                <h2 class="imreg-meta-row__title">Process outbox now</h2>
                <p class="imreg-meta-row__subtitle">Sends up to 25 queued emails, capped at 20 seconds wall-clock. Failed sends are retried (3 attempts total) and then marked <code>failed</code>.</p>
            </div>
            <form method="post" action="<?= htmlspecialchars($baseUrl . '/admin/outbox/process', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="imreg-btn imreg-btn--primary"
                        <?= $counts['queued'] === 0 ? 'disabled' : '' ?>
                        data-imreg-confirm="Process the outbox now? This will attempt to send up to 25 emails.">
                    Process outbox
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php if ($rows === []): ?>
    <div class="imreg-card"><p class="imreg-text-muted imreg-mb-0">No <?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?> emails.</p></div>
<?php else:
    $columns = ['ID', 'To', 'Subject'];
    $rowKeys = ['id', 'to_email', 'subject'];
    if ($tab !== 'sent') {
        $columns[] = 'Attempts';
        $rowKeys[] = 'attempts';
    }
    $columns[] = $tab === 'sent' ? 'Sent at' : 'When';
    $rowKeys[] = $tab === 'sent' ? 'sent_at' : 'queued_at';
    if ($tab !== 'sent') {
        $columns[] = 'Last error';
        $rowKeys[] = '__error';
    }
    if ($tab === 'sent') {
        $columns[] = 'Sent at';
        $rowKeys[] = 'sent_at';
    }
    if ($tab === 'failed') {
        $columns[] = 'Actions';
        $rowKeys[] = '__retry';
    }
    $cellCb = [
        null,
        null,
        null,
    ];
    if ($tab !== 'sent') {
        $cellCb[] = static fn ($r) => '<span class="imreg-text-tabular">' . (int) $r['attempts'] . '</span>';
    }
    $cellCb[] = null; // when
    if ($tab !== 'sent') {
        $cellCb[] = static function ($r) {
            $err = (string) ($r['last_error'] ?? '');
            if ($err === '') {
                return '<span class="imreg-text-muted">—</span>';
            }
            return '<code class="imreg-code-pill">' . htmlspecialchars($err, ENT_QUOTES, 'UTF-8') . '</code>';
        };
    }
    if ($tab === 'sent') {
        $cellCb[] = static fn ($r) => (string) ($r['sent_at'] ?? '');
    }
    if ($tab === 'failed') {
        $cellCb[] = static function ($r) use ($baseUrl, $csrf) {
            $id = (int) $r['id'];
            return '<form method="post" action="' . htmlspecialchars($baseUrl . '/admin/outbox/' . $id . '/retry', ENT_QUOTES, 'UTF-8') . '" class="imreg-form-inline" data-imreg-confirm="Re-queue outbox #' . $id . ' and reset its attempts to 0?">'
                 . '<input type="hidden" name="_csrf" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '">'
                 . '<button type="submit" class="imreg-btn imreg-btn--secondary imreg-btn--sm">Re-queue</button>'
                 . '</form>';
        };
    }
    $empty = '';
    $caption = ucfirst($tab) . ' emails';
    include IMREG_VIEWS_PATH . '/partials/table.php';
endif; ?>

<p class="imreg-hint">
    Showing up to 50 rows per tab. For automatic processing, set up a cPanel cron job pointing at <code>cron/process-outbox.php</code> in the plugin folder (see README).
</p>

<?php
/**
 * Custom endpoint submissions view.
 *
 * @var string  $baseUrl
 * @var array   $endpoint
 * @var array   $filters
 * @var int     $page
 * @var int     $pages
 * @var int     $total
 * @var array   $rows
 * @var int     $perPage
 * @var string  $csrf
 * @var ?string $flash
 */
$endpoint  = is_array($endpoint ?? null) ? $endpoint : [];
$rows      = is_array($rows ?? null) ? $rows : [];
$filters   = is_array($filters ?? null) ? $filters : [];
$listUrl   = $baseUrl . '/admin/custom-endpoints/' . (int) ($endpoint['id'] ?? 0) . '/submissions';
$preserved = [];
if (!empty($filters['search'])) {
    $preserved['search'] = (string) $filters['search'];
}
?>
<?php if (is_string($flash ?? null) && $flash !== ''): ?>
    <div class="imreg-flash imreg-flash--success" role="status" aria-live="polite"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="imreg-meta-row">
    <div>
        <h2 class="imreg-meta-row__title"><?= htmlspecialchars((string) ($endpoint['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="imreg-meta-row__subtitle">slug: <code class="imreg-code-inline"><?= htmlspecialchars((string) ($endpoint['slug'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></p>
    </div>
    <div class="imreg-flex imreg-gap-2">
        <a href="<?= htmlspecialchars($baseUrl . '/admin/custom-endpoints/' . (int) $endpoint['id'] . '/edit', ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--primary">Edit endpoint</a>
        <a href="<?= htmlspecialchars($baseUrl . '/admin/custom-endpoints', ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--ghost">Back to list</a>
    </div>
</div>

<form method="get" action="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" class="imreg-toolbar imreg-toolbar--narrow">
    <div class="imreg-toolbar__field">
        <label for="s-search" class="imreg-toolbar__label">Search inside data JSON</label>
        <input id="s-search" name="search" type="search" placeholder="Any value…"
               value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
               class="imreg-input">
    </div>
    <div class="imreg-toolbar__field--actions">
        <button type="submit" class="imreg-btn imreg-btn--primary">Search</button>
        <a href="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--ghost">Clear</a>
    </div>
</form>

<?php if ($rows === []): ?>
    <div class="imreg-card imreg-card--dashed imreg-text-center">
        <p class="imreg-text-muted imreg-mb-0 imreg-mt-2">No submissions for this endpoint yet.</p>
    </div>
<?php else: ?>
    <?php foreach ($rows as $sub): ?>
        <details class="imreg-collapse">
            <summary>
                <span class="imreg-text-tabular imreg-text-tabular--strong">#<?= (int) $sub['id'] ?></span>
                <span class="imreg-text-muted imreg-text-faint"><?= htmlspecialchars((string) $sub['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                <span class="imreg-badge imreg-badge--tentative"><span class="imreg-sr-only">Status:</span><?= htmlspecialchars((string) $sub['status'], ENT_QUOTES, 'UTF-8') ?></span>
            </summary>
            <pre><?= htmlspecialchars(json_encode($sub['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></pre>
        </details>
    <?php endforeach; ?>
<?php endif; ?>

<?php
$page  = (int) ($page  ?? 1);
$pages = (int) ($pages ?? 0);
$total = (int) ($total ?? 0);
$baseUrlForPagi = $listUrl;
$query = $preserved;
include IMREG_VIEWS_PATH . '/partials/pagination.php';
?>

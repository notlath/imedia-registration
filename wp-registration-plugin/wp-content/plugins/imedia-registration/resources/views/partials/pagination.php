<?php
/**
 * Pagination partial.
 *
 * Expects locals:
 * @var int    $page
 * @var int    $pages
 * @var int    $total
 * @var string $baseUrl      (e.g. /admin/registrations)
 * @var array  $query        (extra query string to preserve)
 */
$page    = max(1, (int) ($page ?? 1));
$pages   = (int) ($pages ?? 0);
$total   = (int) ($total ?? 0);
$baseUrl = (string) ($baseUrl ?? '');
$query   = is_array($query ?? null) ? $query : [];

if ($pages <= 1 && $total <= 0) {
    return;
}

$build = static function (int $p) use ($baseUrl, $query): string {
    $params = $query;
    if ($p > 1) {
        $params['page'] = $p;
    } else {
        unset($params['page']);
    }
    $qs = http_build_query($params);
    return $baseUrl . ($qs !== '' ? '?' . $qs : '');
};

// Compact page list: 1 … (p-1) p (p+1) … last
$window = [];
if ($pages > 0) {
    $window[] = 1;
    for ($i = max(2, $page - 1); $i <= min($pages - 1, $page + 1); $i++) {
        $window[] = $i;
    }
    if ($pages > 1) {
        $window[] = $pages;
    }
    $window = array_values(array_unique($window));
    sort($window);
}
?>
<nav class="imreg-pagination" aria-label="Pagination">
    <?php if ($page > 1): ?>
        <a class="imreg-pagination__link" href="<?= htmlspecialchars($build($page - 1), ENT_QUOTES, 'UTF-8') ?>" rel="prev" aria-label="Previous page">‹</a>
    <?php endif; ?>

    <?php
    $last = 0;
    foreach ($window as $p):
        if ($last > 0 && $p - $last > 1): ?>
            <span class="imreg-pagination__ellipsis" aria-hidden="true">…</span>
        <?php endif;
        $isCurrent = $p === $page;
        if ($isCurrent): ?>
            <span class="imreg-pagination__link imreg-pagination__link--active" aria-current="page"><?= $p ?></span>
        <?php else: ?>
            <a class="imreg-pagination__link" href="<?= htmlspecialchars($build($p), ENT_QUOTES, 'UTF-8') ?>" aria-label="Page <?= $p ?>"><?= $p ?></a>
        <?php endif;
        $last = $p;
    endforeach;
    ?>

    <?php if ($page < $pages): ?>
        <a class="imreg-pagination__link" href="<?= htmlspecialchars($build($page + 1), ENT_QUOTES, 'UTF-8') ?>" rel="next" aria-label="Next page">›</a>
    <?php endif; ?>

    <span class="imreg-pagination__info">
        Page <?= $page ?> of <?= max(1, $pages) ?> &middot; <?= number_format($total) ?> total
    </span>
</nav>

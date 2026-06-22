<?php
/**
 * Applications list view (shared for OJT and Trainer).
 *
 * @var string $baseUrl
 * @var string $type
 * @var array  $filters
 * @var int    $page
 * @var int    $pages
 * @var int    $total
 * @var array  $rows
 * @var int    $perPage
 * @var array  $statuses
 * @var string $csrf
 * @var ?string $flash
 * @var ?string $flashError
 */
$type      = (string) ($type ?? 'ojt');
$statuses  = $statuses ?? ['pending', 'reviewed', 'accepted', 'rejected'];
$preserved = [];
foreach (['status', 'search'] as $k) {
    if (!empty($filters[$k])) {
        $preserved[$k] = (string) $filters[$k];
    }
}
$listUrl   = $baseUrl . '/admin/applications/' . $type;
$exportUrl = $baseUrl . '/admin/export/applications-' . $type . '.csv';
?>
<?php if (is_string($flash ?? null) && $flash !== ''): ?>
    <div class="imreg-flash imreg-flash--success" role="status" aria-live="polite"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="imreg-flex imreg-justify-between imreg-items-center imreg-gap-3 imreg-mb-4" style="flex-wrap:wrap;">
    <form method="get" action="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" class="imreg-toolbar" style="flex:1;margin-bottom:0;">
        <div class="imreg-toolbar__field imreg-toolbar__field--narrow">
            <label for="a-status" class="imreg-toolbar__label">Status</label>
            <select id="a-status" name="status" class="imreg-select">
                <option value="">All</option>
                <?php foreach ($statuses as $s): ?>
                    <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>"
                        <?= ($filters['status'] ?? '') === $s ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($s), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="imreg-toolbar__field">
            <label for="a-search" class="imreg-toolbar__label">Search</label>
            <input id="a-search" name="search" type="search" placeholder="Name, email, position, or mobile"
                   value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   class="imreg-input">
        </div>
        <div class="imreg-toolbar__field--actions">
            <button type="submit" class="imreg-btn imreg-btn--primary">Filter</button>
            <a href="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--ghost">Clear</a>
        </div>
    </form>
    <a href="<?= htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--secondary">Export CSV</a>
</div>

<?php
$appBadgeMap = [
    'pending'  => 'pending',
    'reviewed' => 'tentative',
    'accepted' => 'confirm',
    'rejected' => 'forfeit',
];
$columns = ['ID', 'Name', 'Email', 'Position', 'Status', 'Created', 'Actions'];
$rowKeys = ['id', 'name', 'email', 'position', '__status', 'created_at', '__actions'];
$cellCb = [
    null, null, null, null,
    static function ($r) use ($appBadgeMap) {
        $s = (string) $r['status'];
        $mod = $appBadgeMap[$s] ?? '';
        $class = 'imreg-badge' . ($mod !== '' ? ' imreg-badge--' . $mod : '');
        return '<span class="' . $class . '"><span class="imreg-sr-only">Status:</span>'
             . htmlspecialchars(ucfirst($s), ENT_QUOTES, 'UTF-8') . '</span>';
    },
    null,
    static function ($r) use ($baseUrl, $csrf, $type) {
        $id = (int) $r['id'];
        $delUrl = $baseUrl . '/admin/applications/' . $type . '/' . $id . '/delete';
        return '<form method="post" action="' . htmlspecialchars($delUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline" data-imreg-confirm="Move this application to trash?">'
             . '<input type="hidden" name="_csrf" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '">'
             . '<button type="submit" class="imreg-btn imreg-btn--danger imreg-btn--sm">Delete</button>'
             . '</form>';
    },
];
$rowsTbl = array_map(static function ($r) {
    return [
        'id'         => (int) $r['id'],
        'name'       => (string) ($r['name'] ?? ''),
        'email'      => (string) ($r['email'] ?? ''),
        'position'   => (string) ($r['position'] ?? ''),
        'status'     => (string) $r['status'],
        'created_at' => (string) $r['created_at'],
    ];
}, $rows);
$empty = 'No applications match these filters.';
include IMREG_VIEWS_PATH . '/partials/table.php';

$page  = (int) ($page  ?? 1);
$pages = (int) ($pages ?? 0);
$total = (int) ($total ?? 0);
$baseUrlForPagi = $listUrl;
$query = $preserved;
include IMREG_VIEWS_PATH . '/partials/pagination.php';
?>

<?php
/**
 * Contacts list view.
 *
 * @var string $baseUrl
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
$statuses = $statuses ?? ['pending', 'contacted', 'resolved'];
$preserved = [];
foreach (['status', 'search'] as $k) {
    if (!empty($filters[$k])) {
        $preserved[$k] = (string) $filters[$k];
    }
}
$listUrl = $baseUrl . '/admin/contacts';
?>
<?php if (is_string($flash ?? null) && $flash !== ''): ?>
    <div class="imreg-flash imreg-flash--success" role="status" aria-live="polite"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (is_string($flashError ?? null) && $flashError !== ''): ?>
    <div class="imreg-flash imreg-flash--error" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="imreg-meta-row">
    <form method="get" action="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" class="imreg-toolbar imreg-toolbar--inline">
        <div class="imreg-toolbar__field imreg-toolbar__field--narrow">
            <label for="c-status" class="imreg-toolbar__label">Status</label>
            <select id="c-status" name="status" class="imreg-select">
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
            <label for="c-search" class="imreg-toolbar__label">Search</label>
            <input id="c-search" name="search" type="search" placeholder="Name, email, subject, or mobile"
                   value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                   class="imreg-input">
        </div>
        <div class="imreg-toolbar__field--actions">
            <button type="submit" class="imreg-btn imreg-btn--primary">Filter</button>
            <a href="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--ghost">Clear</a>
        </div>
    </form>
    <a href="<?= htmlspecialchars($baseUrl . '/admin/export/contacts.csv', ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--secondary">Export CSV</a>
</div>

<?php
$columns = ['ID', 'Name', 'Email', 'Subject', 'Status', 'Created', 'Actions'];
$rowKeys = ['id', 'name', 'email', 'subject', '__status', 'created_at', '__actions'];
$contactBadgeMap = ['pending' => 'pending', 'contacted' => 'tentative', 'resolved' => 'confirm'];
$cellCb = [
    null, null, null, null,
    static function ($r) use ($contactBadgeMap) {
        $s = (string) $r['status'];
        $mod = $contactBadgeMap[$s] ?? '';
        $class = 'imreg-badge' . ($mod !== '' ? ' imreg-badge--' . $mod : '');
        return '<span class="' . $class . '"><span class="imreg-sr-only">Status:</span>'
             . htmlspecialchars(ucfirst($s), ENT_QUOTES, 'UTF-8') . '</span>';
    },
    null,
    static function ($r) use ($baseUrl, $csrf) {
        $id = (int) $r['id'];
        $delUrl = $baseUrl . '/admin/contacts/' . $id . '/delete';
        return '<form method="post" action="' . htmlspecialchars($delUrl, ENT_QUOTES, 'UTF-8') . '" class="imreg-form-inline" data-imreg-confirm="Move this contact to trash?">'
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
        'subject'    => (string) ($r['subject'] ?? ''),
        'status'     => (string) $r['status'],
        'created_at' => (string) $r['created_at'],
    ];
}, $rows);
$empty = 'No contacts match these filters.';
include IMREG_VIEWS_PATH . '/partials/table.php';

$page  = (int) ($page  ?? 1);
$pages = (int) ($pages ?? 0);
$total = (int) ($total ?? 0);
$baseUrlForPagi = $listUrl;
$query = $preserved;
include IMREG_VIEWS_PATH . '/partials/pagination.php';
?>

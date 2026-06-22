<?php
/**
 * Alumni (soft-deleted registrations) view.
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
 */
$statuses = $statuses ?? ['pending', 'tentative', 'confirm', 'forfeit', 'reschedule'];
$preserved = [];
if (!empty($filters['search'])) {
    $preserved['search'] = (string) $filters['search'];
}
$alumniUrl = $baseUrl . '/admin/alumni';
?>
<?php if (is_string($flash ?? null) && $flash !== ''): ?>
    <div class="imreg-flash imreg-flash--success" role="status" aria-live="polite"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="get" action="<?= htmlspecialchars($alumniUrl, ENT_QUOTES, 'UTF-8') ?>" class="imreg-toolbar">
    <div class="imreg-toolbar__field">
        <label for="a-search" class="imreg-toolbar__label">Search</label>
        <input id="a-search" name="search" type="search" placeholder="Name, email, or mobile"
               value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
               class="imreg-input">
    </div>
    <div class="imreg-toolbar__field--actions">
        <button type="submit" class="imreg-btn imreg-btn--primary">Search</button>
        <a href="<?= htmlspecialchars($alumniUrl, ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--ghost">Clear</a>
    </div>
</form>

<?php
$columns = ['ID', 'Name', 'Email', 'Course', 'Status', 'Deleted at', 'Actions'];
$rowKeys = ['id', 'name', 'email', 'course', '__status', 'deleted_at', '__actions'];
$cellCb = [
    null,
    null,
    null,
    null,
    static function (array $row): string {
        $status = (string) ($row['status'] ?? '');
        $label  = ucfirst($status);
        $mod    = in_array($status, ['pending', 'tentative', 'confirm', 'forfeit', 'reschedule'], true) ? $status : '';
        $class  = 'imreg-badge' . ($mod !== '' ? ' imreg-badge--' . $mod : '');
        return '<span class="' . $class . '"><span class="imreg-sr-only">Status:</span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    },
    null,
    static function (array $row) use ($baseUrl): string {
        $id  = (int) ($row['id'] ?? 0);
        return '<button type="button" class="imreg-btn imreg-btn--primary imreg-btn--sm" '
             . 'data-imreg-open-dialog="imreg-restore-dialog-' . $id . '">Restore…</button>';
    },
];
$rowsTbl = array_map(static function (array $r): array {
    return [
        'id'         => (int) $r['id'],
        'name'       => (string) $r['name'],
        'email'      => (string) $r['email'],
        'course'     => (string) $r['course'],
        'status'     => (string) $r['status'],
        'deleted_at' => (string) ($r['deleted_at'] ?? ''),
    ];
}, $rows);
$empty = 'No alumni match this search.';
$caption = '';
include IMREG_VIEWS_PATH . '/partials/table.php';

$page  = (int) ($page  ?? 1);
$pages = (int) ($pages ?? 0);
$total = (int) ($total ?? 0);
$baseUrlForPagi = $alumniUrl;
$query = $preserved;
include IMREG_VIEWS_PATH . '/partials/pagination.php';
?>

<?php foreach ($rows as $row): $id = (int) $row['id']; include IMREG_VIEWS_PATH . '/partials/restore-modal.php'; endforeach; ?>

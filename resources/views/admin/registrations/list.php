<?php
/**
 * Registrations list view.
 *
 * @var string $baseUrl
 * @var array  $filters
 * @var int    $page
 * @var int    $pages
 * @var int    $total
 * @var array  $rows
 * @var int    $perPage
 * @var array  $statuses
 * @var array  $courses
 * @var string $csrf
 * @var ?string $flash
 * @var ?string $flashError
 */
$statuses = $statuses ?? ['pending', 'tentative', 'confirm', 'forfeit', 'reschedule'];
$preserved = [];
foreach (['status', 'course', 'search', 'date_from', 'date_to'] as $k) {
    if (!empty($filters[$k])) {
        $preserved[$k] = (string) $filters[$k];
    }
}
$listUrl = $baseUrl . '/admin/registrations';
?>
<?php if (is_string($flash) && $flash !== ''): ?>
    <div class="imreg-flash imreg-flash--success" role="status" aria-live="polite"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (is_string($flashError) && $flashError !== ''): ?>
    <div class="imreg-flash imreg-flash--error" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="get" action="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" class="imreg-toolbar" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr)) auto;">
    <div class="imreg-toolbar__field">
        <label for="f-status" class="imreg-toolbar__label">Status</label>
        <select id="f-status" name="status" class="imreg-select">
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
        <label for="f-course" class="imreg-toolbar__label">Course</label>
        <select id="f-course" name="course" class="imreg-select">
            <option value="">All</option>
            <?php foreach ($courses as $c): ?>
                <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>"
                    <?= ($filters['course'] ?? '') === $c ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="imreg-toolbar__field">
        <label for="f-search" class="imreg-toolbar__label">Search</label>
        <input id="f-search" name="search" type="search" placeholder="Name, email, or mobile"
               value="<?= htmlspecialchars((string) ($filters['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
               class="imreg-input">
    </div>
    <div class="imreg-toolbar__field">
        <label for="f-date_from" class="imreg-toolbar__label">From</label>
        <input id="f-date_from" name="date_from" type="date"
               value="<?= htmlspecialchars((string) ($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
               class="imreg-input">
    </div>
    <div class="imreg-toolbar__field">
        <label for="f-date_to" class="imreg-toolbar__label">To</label>
        <input id="f-date_to" name="date_to" type="date"
               value="<?= htmlspecialchars((string) ($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
               class="imreg-input">
    </div>
    <div class="imreg-toolbar__field--actions">
        <button type="submit" class="imreg-btn imreg-btn--primary">Filter</button>
        <a href="<?= htmlspecialchars($listUrl, ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--ghost">Clear</a>
    </div>
</form>

<form method="post" action="<?= htmlspecialchars($baseUrl . '/admin/registrations/bulk-status', ENT_QUOTES, 'UTF-8') ?>" id="imreg-bulk-form" class="imreg-flex imreg-items-center imreg-gap-3 imreg-mb-4" style="flex-wrap:wrap;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <label for="bulk-status" class="imreg-label">Bulk set status:</label>
    <select id="bulk-status" name="new_status" class="imreg-select" style="width:auto;display:inline-block;">
        <?php foreach ($statuses as $s): ?>
            <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(ucfirst($s), ENT_QUOTES, 'UTF-8') ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="imreg-btn imreg-btn--secondary">Apply to selected</button>
    <span class="imreg-text-muted" style="font-size:0.75rem;">(<?= (int) $total ?> total)</span>
</form>

<?php
$paymentBadgeMap = [
    'pending'    => '',
    'deposit'    => 'pending',
    'fully_paid' => 'confirm',
];
$columns = ['', 'ID', 'Name', 'Email', 'Course', 'Start', 'Status', 'Payment', 'Created', 'Actions'];
$rowKeys = ['__select', 'id', 'name', 'email', 'course', 'start_date', 'status', 'payment_status', 'created_at', '__actions'];
$cellCb = [
    static function ($r) {
        $id = (int) $r['id'];
        return '<input type="checkbox" name="ids[]" value="' . $id . '" form="imreg-bulk-form" aria-label="Select row ' . $id . '">';
    },
    null, null, null, null, null,
    static function ($r) {
        $s = (string) $r['status'];
        $mod = in_array($s, ['pending', 'tentative', 'confirm', 'forfeit', 'reschedule'], true) ? $s : '';
        $class = 'imreg-badge' . ($mod !== '' ? ' imreg-badge--' . $mod : '');
        return '<span class="' . $class . '"><span class="imreg-sr-only">Status:</span>'
             . htmlspecialchars(ucfirst($s), ENT_QUOTES, 'UTF-8') . '</span>';
    },
    static function ($r) use ($paymentBadgeMap) {
        $s = (string) $r['payment_status'];
        $mod = $paymentBadgeMap[$s] ?? '';
        $class = 'imreg-badge' . ($mod !== '' ? ' imreg-badge--' . $mod : '');
        $label = ucfirst(str_replace('_', ' ', $s));
        return '<span class="' . $class . '"><span class="imreg-sr-only">Payment status:</span>'
             . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    },
    null,
    static function ($r) use ($baseUrl) {
        $id = (int) $r['id'];
        $viewUrl = $baseUrl . '/admin/registrations/' . $id;
        $editUrl = $viewUrl . '/edit';
        $delUrl  = $viewUrl . '/delete';
        $csrfField = '<input type="hidden" name="_csrf" value="' . htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') . '">';
        return '<a href="' . htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') . '" class="imreg-btn imreg-btn--ghost imreg-btn--sm">View</a> '
             . '<a href="' . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '" class="imreg-btn imreg-btn--primary imreg-btn--sm">Edit</a> '
             . '<form method="post" action="' . htmlspecialchars($delUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline" data-imreg-confirm="Soft-delete this registration? It will move to Alumni.">'
             . $csrfField
             . '<button type="submit" class="imreg-btn imreg-btn--danger imreg-btn--sm">Delete</button>'
             . '</form>';
    },
];
$rowsTbl = array_map(static function ($r) {
    return [
        '__select'      => $r,
        'id'            => (int) $r['id'],
        'name'          => (string) $r['name'],
        'email'         => (string) $r['email'],
        'course'        => (string) $r['course'],
        'start_date'    => (string) $r['start_date'],
        'status'        => (string) $r['status'],
        'payment_status'=> (string) $r['payment_status'],
        'created_at'    => (string) $r['created_at'],
        '__actions'     => $r,
    ];
}, $rows);
$empty = 'No registrations match these filters.';
include IMREG_VIEWS_PATH . '/partials/table.php';

$page  = (int) ($page  ?? 1);
$pages = (int) ($pages ?? 0);
$total = (int) ($total ?? 0);
$baseUrlForPagi = $listUrl;
$query = $preserved;
include IMREG_VIEWS_PATH . '/partials/pagination.php';
?>

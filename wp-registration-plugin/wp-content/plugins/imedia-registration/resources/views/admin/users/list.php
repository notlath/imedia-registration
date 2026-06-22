<?php
/**
 * Admin users list view.
 *
 * @var string  $baseUrl
 * @var array   $admins
 * @var int     $count
 * @var ?int    $currentId
 * @var string  $csrf
 * @var ?string $flash
 * @var ?string $flashError
 */
$admins    = is_array($admins ?? null) ? $admins : [];
$currentId = (int) ($currentId ?? 0);
?>
<?php if (is_string($flash ?? null) && $flash !== ''): ?>
    <div class="imreg-flash imreg-flash--success" role="status" aria-live="polite"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (is_string($flashError ?? null) && $flashError !== ''): ?>
    <div class="imreg-flash imreg-flash--error" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="imreg-flex imreg-justify-between imreg-items-center imreg-mb-4">
    <p class="imreg-text-muted imreg-mb-0"><?= (int) $count ?> admin user<?= $count === 1 ? '' : 's' ?>.</p>
    <a href="<?= htmlspecialchars($baseUrl . '/admin/users/new', ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--primary">+ New admin</a>
</div>

<?php
$columns = ['ID', 'Name', 'Email', 'Role', 'Created', 'Actions'];
$rowKeys = ['id', 'name', 'email', 'role', 'created_at', '__actions'];
$cellCb = [
    null, null, null,
    static function ($r) {
        $role = (string) $r['role'];
        $mod  = in_array($role, ['admin', 'super'], true) ? $role : '';
        $class = 'imreg-badge' . ($mod !== '' ? ' imreg-badge--' . ($role === 'super' ? 'pending' : 'tentative') : '');
        return '<span class="' . $class . '"><span class="imreg-sr-only">Role:</span>' . htmlspecialchars(ucfirst($role), ENT_QUOTES, 'UTF-8') . '</span>';
    },
    null,
    static function ($r) use ($baseUrl, $csrf, $currentId, $count) {
        $id = (int) $r['id'];
        $isSelf    = $id === $currentId;
        $isLastOne = $count <= 1;
        $editUrl   = $baseUrl . '/admin/users/' . $id . '/edit';
        $delUrl    = $baseUrl . '/admin/users/' . $id . '/delete';
        $btn = '<a href="' . htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') . '" class="imreg-btn imreg-btn--ghost imreg-btn--sm">Edit</a> ';
        if ($isSelf) {
            $btn .= '<span class="imreg-btn imreg-btn--sm imreg-btn--ghost" style="text-decoration:line-through;cursor:not-allowed;opacity:0.5;" title="You cannot delete your own account.">Delete</span>';
        } elseif ($isLastOne) {
            $btn .= '<span class="imreg-btn imreg-btn--sm imreg-btn--ghost" style="text-decoration:line-through;cursor:not-allowed;opacity:0.5;" title="Cannot delete the last admin.">Delete</span>';
        } else {
            $btn .= '<form method="post" action="' . htmlspecialchars($delUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline" data-imreg-confirm="Delete this admin?">'
                  . '<input type="hidden" name="_csrf" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '">'
                  . '<button type="submit" class="imreg-btn imreg-btn--danger imreg-btn--sm">Delete</button>'
                  . '</form>';
        }
        return $btn;
    },
];
$rowsTbl = array_map(static fn ($r) => [
    'id'         => (int) $r['id'],
    'name'       => (string) $r['name'],
    'email'      => (string) $r['email'],
    'role'       => (string) $r['role'],
    'created_at' => (string) $r['created_at'],
    '__actions'  => $r,
], $admins);
$empty = 'No admins yet.';
include IMREG_VIEWS_PATH . '/partials/table.php';
?>

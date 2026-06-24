<?php
/**
 * Custom endpoints list view.
 *
 * @var string $baseUrl
 * @var array  $endpoints
 * @var string $csrf
 * @var ?string $flash
 * @var ?string $flashError
 */
$endpoints = is_array($endpoints ?? null) ? $endpoints : [];
?>
<?php if (is_string($flash ?? null) && $flash !== ''): ?>
    <div class="imreg-flash imreg-flash--success" role="status" aria-live="polite"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (is_string($flashError ?? null) && $flashError !== ''): ?>
    <div class="imreg-flash imreg-flash--error" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="imreg-flex imreg-justify-between imreg-items-center imreg-mb-4">
    <p class="imreg-text-muted imreg-mb-0"><?= count($endpoints) ?> custom endpoint<?= count($endpoints) === 1 ? '' : 's' ?> configured.</p>
    <a href="<?= htmlspecialchars($baseUrl . '/admin/custom-endpoints/new', ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--primary">+ New endpoint</a>
</div>

<?php if ($endpoints === []): ?>
    <div class="imreg-card imreg-card--dashed imreg-text-center">
        <p class="imreg-text-muted imreg-mb-0 imreg-mt-2">No custom endpoints configured yet.</p>
        <p class="imreg-text-muted imreg-mt-2">Custom endpoints let the WordPress plugin submit to a fully dynamic schema. Create one to get started.</p>
    </div>
<?php else:
    $columns = ['Name', 'Slug', 'Submissions', 'Actions'];
    $rowKeys = ['name', 'slug', 'submissions_count', '__actions'];
    $cellCb = [
        null, null,
        static fn ($r) => '<span class="imreg-text-tabular">' . (int) $r['submissions_count'] . '</span>',
        static function ($r) use ($baseUrl, $csrf) {
            $id = (int) $r['id'];
            return '<a href="' . htmlspecialchars($baseUrl . '/admin/custom-endpoints/' . $id . '/edit', ENT_QUOTES, 'UTF-8') . '" class="imreg-btn imreg-btn--ghost imreg-btn--sm">Edit</a> '
                 . '<a href="' . htmlspecialchars($baseUrl . '/admin/custom-endpoints/' . $id . '/submissions', ENT_QUOTES, 'UTF-8') . '" class="imreg-btn imreg-btn--secondary imreg-btn--sm">Submissions</a> '
                 . '<form method="post" action="' . htmlspecialchars($baseUrl . '/admin/custom-endpoints/' . $id . '/delete', ENT_QUOTES, 'UTF-8') . '" class="imreg-form-inline" data-imreg-confirm="Delete this endpoint AND all its submissions? This cannot be undone.">'
                 . '<input type="hidden" name="_csrf" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '">'
                 . '<button type="submit" class="imreg-btn imreg-btn--danger imreg-btn--sm">Delete</button>'
                 . '</form>';
        },
    ];
    $rowsTbl = array_map(static fn ($r) => [
        'name'              => (string) $r['name'],
        'slug'              => (string) $r['slug'],
        'submissions_count' => (int) $r['submissions_count'],
        '__actions'         => $r,
    ], $endpoints);
    $empty = 'No custom endpoints configured.';
    include IMREG_VIEWS_PATH . '/partials/table.php';
endif; ?>

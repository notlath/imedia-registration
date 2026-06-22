<?php
/**
 * Custom endpoint create/edit form.
 *
 * @var string $mode
 * @var ?int   $id
 * @var array  $endpoint
 * @var string $action
 * @var string $submitText
 * @var string $baseUrl
 * @var string $csrf
 * @var array  $errors
 * @var ?string $errorMsg
 */
$mode       = (string) ($mode ?? 'create');
$endpoint   = is_array($endpoint ?? null) ? $endpoint : [];
$action     = (string) ($action ?? '');
$submitText = (string) ($submitText ?? 'Save');
$baseUrl    = (string) ($baseUrl ?? '');
$csrf       = (string) ($csrf ?? '');
$errors     = is_array($errors ?? null) ? $errors : [];
$errorMsg   = $errorMsg ?? null;

$name        = (string) ($endpoint['name']   ?? '');
$slug        = (string) ($endpoint['slug']   ?? '');
$icon        = (string) ($endpoint['icon']   ?? '');
$fieldsJson  = (string) ($endpoint['fields_json_pretty']   ?? '[]');
$statusesJson = (string) ($endpoint['statuses_json_pretty'] ?? '["pending"]');

// Apply old() on top of endpoint values.
foreach (['name', 'slug', 'icon'] as $k) {
    $ov = \App\Core\View::old($k, null);
    if ($ov !== null) {
        $$k = (string) $ov;
    }
}
foreach (['fieldsJson', 'statusesJson'] as $k) {
    $ov = \App\Core\View::old($k, null);
    if ($ov !== null) {
        $$k = (string) $ov;
    }
}
?>
<?php if (is_string($errorMsg) && $errorMsg !== ''): ?>
    <div class="imreg-flash imreg-flash--error" role="alert"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" novalidate class="imreg-card" style="max-width:760px;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <div class="imreg-grid--form">
        <div class="imreg-field">
            <label for="ep-name" class="imreg-label">Name <span class="imreg-label__required" aria-hidden="true">*</span></label>
            <input id="ep-name" name="name" required value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" class="imreg-input <?= isset($errors['name']) ? 'imreg-input--error' : '' ?>">
            <?php if (isset($errors['name'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field">
            <label for="ep-slug" class="imreg-label">Slug <span class="imreg-label__required" aria-hidden="true">*</span></label>
            <input id="ep-slug" name="slug" required pattern="[a-z0-9][a-z0-9_-]{1,99}" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>" class="imreg-input imreg-input--code <?= isset($errors['slug']) ? 'imreg-input--error' : '' ?>">
            <div class="imreg-help">Lowercase letters, digits, dash, or underscore. Used as the public target slug.</div>
            <?php if (isset($errors['slug'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['slug'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field">
            <label for="ep-icon" class="imreg-label">Icon <span class="imreg-text-muted" style="font-weight:400;">(optional)</span></label>
            <input id="ep-icon" name="icon" value="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" placeholder="table_view" class="imreg-input">
            <div class="imreg-help">Material Icons name (e.g. <code>table_view</code>).</div>
        </div>
        <div class="imreg-field imreg-field--full">
            <label for="ep-fields" class="imreg-label">Fields (JSON array)</label>
            <textarea id="ep-fields" name="fields" rows="8" class="imreg-textarea imreg-input--code <?= isset($errors['fields']) ? 'imreg-input--error' : '' ?>"><?= htmlspecialchars($fieldsJson, ENT_QUOTES, 'UTF-8') ?></textarea>
            <div class="imreg-help">Example: <code>[{"name":"email","label":"Email","type":"email","required":true},{"name":"message","label":"Message","type":"textarea"}]</code></div>
            <?php if (isset($errors['fields'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['fields'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field imreg-field--full">
            <label for="ep-statuses" class="imreg-label">Statuses (JSON array of strings)</label>
            <textarea id="ep-statuses" name="statuses" rows="3" class="imreg-textarea imreg-input--code <?= isset($errors['statuses']) ? 'imreg-input--error' : '' ?>"><?= htmlspecialchars($statusesJson, ENT_QUOTES, 'UTF-8') ?></textarea>
            <div class="imreg-help">Example: <code>["pending","reviewed","resolved"]</code></div>
            <?php if (isset($errors['statuses'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['statuses'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="imreg-actions imreg-justify-between">
        <div class="imreg-flex imreg-gap-2">
            <button type="submit" class="imreg-btn imreg-btn--primary"><?= htmlspecialchars($submitText, ENT_QUOTES, 'UTF-8') ?></button>
            <a href="<?= htmlspecialchars($baseUrl . '/admin/custom-endpoints', ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--ghost">Cancel</a>
        </div>
        <?php if ($mode === 'edit'): ?>
            <a href="<?= htmlspecialchars($baseUrl . '/admin/custom-endpoints/' . (int) $id . '/submissions', ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--secondary">View submissions</a>
        <?php endif; ?>
    </div>
</form>

<?php
/**
 * My account (profile) view.
 *
 * @var string  $baseUrl
 * @var array   $admin
 * @var string  $csrf
 * @var array   $errors
 * @var ?string $errorMsg
 * @var ?string $flash
 */
$admin    = is_array($admin ?? null) ? $admin : [];
$csrf     = (string) ($csrf ?? '');
$errors   = is_array($errors ?? null) ? $errors : [];
$errorMsg = $errorMsg ?? null;
$name  = (string) ($admin['name']  ?? '');
$email = (string) ($admin['email'] ?? '');
foreach (['name', 'email'] as $k) {
    $ov = \App\Core\View::old($k, null);
    if ($ov !== null) {
        $$k = (string) $ov;
    }
}
?>
<?php if (is_string($flash ?? null) && $flash !== ''): ?>
    <div class="imreg-flash imreg-flash--success" role="status" aria-live="polite"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (is_string($errorMsg) && $errorMsg !== ''): ?>
    <div class="imreg-flash imreg-flash--error" role="alert" aria-live="assertive"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($baseUrl . '/admin/profile', ENT_QUOTES, 'UTF-8') ?>" novalidate class="imreg-card" style="max-width:560px;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <div class="imreg-grid--form">
        <div class="imreg-field">
            <label for="p-name" class="imreg-label">Name <span class="imreg-label__required" aria-hidden="true">*</span></label>
            <input id="p-name" name="name" required value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" class="imreg-input <?= isset($errors['name']) ? 'imreg-input--error' : '' ?>">
            <?php if (isset($errors['name'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field">
            <label for="p-email" class="imreg-label">Email <span class="imreg-label__required" aria-hidden="true">*</span></label>
            <input id="p-email" name="email" type="email" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" class="imreg-input <?= isset($errors['email']) ? 'imreg-input--error' : '' ?>">
            <?php if (isset($errors['email'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <h3 class="imreg-text-display" style="font-size:0.9375rem;font-weight:600;margin:1.5rem 0 0.5rem;">Change password <span class="imreg-text-muted" style="font-weight:400;">(optional)</span></h3>
    <div class="imreg-grid--form">
        <div class="imreg-field">
            <label for="p-current" class="imreg-label">Current password</label>
            <input id="p-current" name="current_password" type="password" autocomplete="current-password" class="imreg-input <?= isset($errors['current_password']) ? 'imreg-input--error' : '' ?>">
            <?php if (isset($errors['current_password'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['current_password'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field">
            <label for="p-new" class="imreg-label">New password <span class="imreg-text-muted" style="font-weight:400;">(at least 8 characters)</span></label>
            <input id="p-new" name="new_password" type="password" autocomplete="new-password" class="imreg-input <?= isset($errors['new_password']) ? 'imreg-input--error' : '' ?>">
            <?php if (isset($errors['new_password'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['new_password'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field">
            <label for="p-confirm" class="imreg-label">Confirm new password</label>
            <input id="p-confirm" name="confirm_password" type="password" autocomplete="new-password" class="imreg-input <?= isset($errors['confirm_password']) ? 'imreg-input--error' : '' ?>">
            <?php if (isset($errors['confirm_password'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['confirm_password'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="imreg-actions imreg-actions--end">
        <button type="submit" class="imreg-btn imreg-btn--primary">Save changes</button>
        <a href="<?= htmlspecialchars($baseUrl . '/admin', ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--ghost">Cancel</a>
    </div>
</form>

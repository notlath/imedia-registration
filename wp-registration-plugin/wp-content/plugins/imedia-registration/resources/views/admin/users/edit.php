<?php
/**
 * Admin user create/edit form.
 *
 * @var string $mode
 * @var ?int   $id
 * @var array  $user
 * @var array  $roles
 * @var bool   $isSelf
 * @var string $action
 * @var string $submitText
 * @var string $baseUrl
 * @var bool   $showPassword
 * @var string $csrf
 * @var array  $errors
 * @var ?string $errorMsg
 */
$mode       = (string) ($mode ?? 'create');
$user       = is_array($user ?? null) ? $user : [];
$roles      = is_array($roles ?? null) ? $roles : ['admin', 'super'];
$isSelf     = (bool) ($isSelf ?? false);
$action     = (string) ($action ?? '');
$submitText = (string) ($submitText ?? 'Save');
$baseUrl    = (string) ($baseUrl ?? '');
$csrf       = (string) ($csrf ?? '');
$errors     = is_array($errors ?? null) ? $errors : [];
$errorMsg   = $errorMsg ?? null;

$name  = (string) ($user['name']  ?? '');
$email = (string) ($user['email'] ?? '');
$role  = (string) ($user['role']  ?? 'admin');
foreach (['name', 'email', 'role'] as $k) {
    $ov = \App\Core\View::old($k, null);
    if ($ov !== null) {
        $$k = (string) $ov;
    }
}
?>
<?php if (is_string($errorMsg) && $errorMsg !== ''): ?>
    <div class="imreg-flash imreg-flash--error" role="alert"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" novalidate class="imreg-card" style="max-width:640px;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <div class="imreg-grid--form">
        <div class="imreg-field">
            <label for="u-name" class="imreg-label">Name <span class="imreg-label__required" aria-hidden="true">*</span></label>
            <input id="u-name" name="name" required value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" class="imreg-input <?= isset($errors['name']) ? 'imreg-input--error' : '' ?>">
            <?php if (isset($errors['name'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['name'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field">
            <label for="u-email" class="imreg-label">Email <span class="imreg-label__required" aria-hidden="true">*</span></label>
            <input id="u-email" name="email" type="email" required value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" class="imreg-input <?= isset($errors['email']) ? 'imreg-input--error' : '' ?>">
            <?php if (isset($errors['email'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['email'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field">
            <label for="u-role" class="imreg-label">Role</label>
            <select id="u-role" name="role" <?= $isSelf ? 'disabled' : '' ?> class="imreg-select <?= $isSelf ? 'imreg-input--error' : '' ?>" style="<?= $isSelf ? 'opacity:0.6;cursor:not-allowed;' : '' ?>">
                <?php foreach ($roles as $r): ?>
                    <option value="<?= htmlspecialchars($r, ENT_QUOTES, 'UTF-8') ?>" <?= $role === $r ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($r), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($isSelf): ?>
                <div class="imreg-help">You cannot change your own role.</div>
            <?php endif; ?>
            <?php if (isset($errors['role'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['role'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
        <div class="imreg-field imreg-field--full">
            <label for="u-password" class="imreg-label">Password <span class="imreg-text-muted" style="font-weight:400;">(<?= $mode === 'create' ? 'required, at least 8 characters' : 'leave blank to keep current' ?>)</span></label>
            <input id="u-password" name="password" type="password" autocomplete="new-password" <?= $mode === 'create' ? 'required' : '' ?> class="imreg-input <?= isset($errors['password']) ? 'imreg-input--error' : '' ?>">
            <?php if (isset($errors['password'])): ?>
                <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="imreg-actions imreg-actions--end">
        <button type="submit" class="imreg-btn imreg-btn--primary"><?= htmlspecialchars($submitText, ENT_QUOTES, 'UTF-8') ?></button>
        <a href="<?= htmlspecialchars($baseUrl . '/admin/users', ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--ghost">Cancel</a>
    </div>
</form>

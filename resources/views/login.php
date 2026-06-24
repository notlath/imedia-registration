<?php
/**
 * @var string $baseUrl
 * @var ?string $flash
 * @var string $prefill
 */
$baseUrl = (string) ($baseUrl ?? '');
$prefill = (string) ($prefill ?? '');
?>
<section class="imreg-page imreg-page--narrow imreg-anim-slide-up">
    <div class="imreg-card">
        <h1 class="imreg-page-title imreg-page-title--sm">Sign in</h1>
        <p class="imreg-text-muted imreg-mb-6">Inventive Media Registration</p>

        <?php if (is_string($flash) && $flash !== ''): ?>
            <div class="imreg-flash imreg-flash--error" role="alert" aria-live="assertive"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars($baseUrl . '/admin/login', ENT_QUOTES, 'UTF-8') ?>" novalidate>
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

            <div class="imreg-field imreg-mb-4">
                <label for="email" class="imreg-label">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" required
                       value="<?= htmlspecialchars($prefill, ENT_QUOTES, 'UTF-8') ?>"
                       class="imreg-input">
            </div>

            <div class="imreg-field imreg-mb-6">
                <label for="password" class="imreg-label">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required class="imreg-input">
            </div>

            <button type="submit" class="imreg-btn imreg-btn--primary imreg-btn--block imreg-btn--lg">Sign in</button>
        </form>
    </div>
</section>

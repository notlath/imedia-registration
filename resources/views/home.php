<?php
/**
 * @var string $baseUrl
 */
$baseUrl = (string) ($baseUrl ?? '');
?>
<section class="imreg-page imreg-anim-fade-in">
    <h1 class="imreg-page-title">Inventive Media Registration</h1>
    <p class="imreg-page-subtitle">
        This standalone app receives HMAC-signed form submissions from the
        IMedia Forms WordPress plugin and stores them in a separate MySQL
        database.
    </p>
    <p class="imreg-mt-6">
        <a href="<?= htmlspecialchars($baseUrl . '/admin/login', ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--primary">Admin sign-in</a>
    </p>
</section>

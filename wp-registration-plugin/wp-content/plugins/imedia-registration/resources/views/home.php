<?php
/**
 * @var string $baseUrl
 */
$baseUrl = (string) ($baseUrl ?? '');
?>
<section style="max-width:42rem;margin:4rem auto;padding:0 1.5rem;" class="imreg-anim-fade-in">
    <h1 class="imreg-text-display" style="font-size:1.875rem;font-weight:700;line-height:1.2;margin:0 0 0.5rem;letter-spacing:-0.02em;">Inventive Media Registration</h1>
    <p class="imreg-text-muted" style="line-height:1.6;font-size:0.9375rem;">
        This standalone PHP app receives HMAC-signed form submissions from the
        IMedia Forms WordPress plugin and stores them in a separate MySQL
        database.
    </p>
    <p style="margin-top:1.5rem;">
        <a href="<?= htmlspecialchars($baseUrl . '/admin/login', ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--primary">Admin sign-in</a>
    </p>
</section>

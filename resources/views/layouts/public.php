<?php
/**
 * @var string  $__content
 * @var string  $__title
 * @var string  $baseUrl
 */
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title><?= htmlspecialchars($__title !== '' ? $__title : 'IMedia Registration', ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700&family=JetBrains+Mono&display=swap">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl . '/assets/css/app.css', ENT_QUOTES, 'UTF-8') ?>">

    <script>
        (function () {
            try {
                var stored = localStorage.getItem('imreg-theme') || 'auto';
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                var dark = stored === 'dark' || (stored === 'auto' && prefersDark);
                if (dark) document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>
</head>
<body>
    <a class="imreg-skip-link" href="#imreg-main">Skip to main content</a>
    <main id="imreg-main" tabindex="-1" class="imreg-flex-col" style="min-height:100dvh;">
        <?= $__content ?>
    </main>
    <footer style="padding:1.5rem 1rem;text-align:center;color:var(--color-on-surface-variant);font-size:0.875rem;border-top:1px solid var(--color-outline-variant);">
        Powered by Inventive Media &middot;
        <a href="<?= htmlspecialchars($baseUrl . '/admin/login', ENT_QUOTES, 'UTF-8') ?>" style="color:inherit;">Admin sign-in</a>
    </footer>
    <script src="<?= htmlspecialchars($baseUrl . '/assets/js/app.js', ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>

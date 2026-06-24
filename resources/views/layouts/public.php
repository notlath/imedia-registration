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
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600&family=JetBrains+Mono&display=swap">
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
    <main id="imreg-main" tabindex="-1" class="imreg-flex-col imreg-public-main">
        <?= $__content ?>
    </main>
    <footer class="imreg-public-footer">
        Powered by Inventive Media &middot;
        <a href="<?= htmlspecialchars($baseUrl . '/admin/login', ENT_QUOTES, 'UTF-8') ?>">Admin sign-in</a>
    </footer>
    <script src="<?= htmlspecialchars($baseUrl . '/assets/js/app.js', ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>

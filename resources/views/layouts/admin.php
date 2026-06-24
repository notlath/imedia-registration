<?php
/**
 * Admin layout — sidebar + topbar + main.
 *
 * Phase 6: hand-written CSS design system. Replaces inline styles with
 * the .imreg-* component classes from public/assets/css/app.css.
 *
 * @var string  $__content
 * @var string  $__title
 * @var ?array  $admin       (Auth::admin())
 * @var ?string $flash
 * @var string  $baseUrl
 * @var string  $csrf
 */
$adminName = is_array($admin ?? null) ? (string) ($admin['name'] ?? 'Admin') : 'Admin';
$baseUrl   = (string) ($baseUrl ?? '');
$csrf      = (string) ($csrf ?? '');

$urls = [
    'admin'         => $baseUrl . '/admin',
    'registrations' => $baseUrl . '/admin/registrations',
    'alumni'        => $baseUrl . '/admin/alumni',
    'alerts'        => $baseUrl . '/admin/alerts',
    'outbox'        => $baseUrl . '/admin/outbox',
    'contacts'      => $baseUrl . '/admin/contacts',
    'ojt'           => $baseUrl . '/admin/applications/ojt',
    'trainer'       => $baseUrl . '/admin/applications/trainer',
    'custom'        => $baseUrl . '/admin/custom-endpoints',
    'users'         => $baseUrl . '/admin/users',
    'settings'      => $baseUrl . '/admin/settings',
    'profile'       => $baseUrl . '/admin/profile',
    'logout'        => $baseUrl . '/admin/logout',
    'export_reg'    => $baseUrl . '/admin/export/registrations.csv',
];

// Highlight the current top-level section.
$current = (string) ($__title ?? '');
$isActive = static function (array $needles) use ($current): bool {
    foreach ($needles as $n) {
        if (stripos($current, $n) !== false) {
            return true;
        }
    }
    return false;
};
$actReg    = $isActive(['Registration', 'Alumni', 'Alerts']);
$actInq    = $isActive(['Contact', 'OJT', 'Trainer', 'Custom', 'Submission']);
$actAdm    = $isActive(['Admin user', 'Setting']);

// Phase 5 sidebar badge: queued outbox count.
$outboxQueued = 0;
try {
    $outboxQueued = \App\Models\OutboxEmail::count(\App\Models\OutboxEmail::STATUS_QUEUED);
} catch (\Throwable) {
    $outboxQueued = 0;
}
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="strict-origin-when-cross-origin">
    <title><?= htmlspecialchars($__title !== '' ? $__title . ' · IMedia Registration' : 'IMedia Registration', ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600&family=JetBrains+Mono&display=swap">
    <link rel="stylesheet" href="<?= htmlspecialchars($baseUrl . '/assets/css/app.css', ENT_QUOTES, 'UTF-8') ?>">

    <script>
        // Theme bootstrap: read localStorage and apply before first paint.
        (function () {
            try {
                var stored = localStorage.getItem('imreg-theme') || 'auto';
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                var dark = stored === 'dark' || (stored === 'auto' && prefersDark);
                if (dark) document.documentElement.classList.add('dark');
            } catch (e) { /* localStorage may be blocked */ }
        })();
    </script>
</head>
<body>
    <a class="imreg-skip-link" href="#imreg-main">Skip to main content</a>
    <div class="imreg-shell" data-sidebar-open="false">
        <div class="imreg-drawer-backdrop" data-imreg-drawer-backdrop></div>
        <aside class="imreg-sidebar" aria-label="Main">
            <h1 class="imreg-brand"><span class="imreg-brand__mark" aria-hidden="true"></span>IMedia Registration</h1>
            <nav class="imreg-nav" aria-label="Admin navigation">
                <p class="imreg-nav-group">Overview</p>
                <ul>
                    <li>
                        <a class="imreg-nav-link" href="<?= htmlspecialchars($urls['admin'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $isActive(['Dashboard']) ? 'aria-current="page"' : '' ?>>Dashboard</a>
                    </li>
                </ul>

                <p class="imreg-nav-group">Registrations</p>
                <ul>
                    <li>
                        <a class="imreg-nav-link" href="<?= htmlspecialchars($urls['registrations'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $actReg && $isActive(['Registration #', 'Edit Registration', 'New Registration']) ? 'aria-current="page"' : '' ?>
                            <?= $isActive(['Registrations']) && !$isActive(['Alumni']) && !$isActive(['Alerts']) ? 'aria-current="page"' : '' ?>>Registrations</a>
                    </li>
                    <li>
                        <a class="imreg-nav-link" href="<?= htmlspecialchars($urls['alumni'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $isActive(['Alumni']) ? 'aria-current="page"' : '' ?>>Alumni</a>
                    </li>
                    <li>
                        <a class="imreg-nav-link" href="<?= htmlspecialchars($urls['alerts'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $isActive(['Alerts', 'Threshold']) ? 'aria-current="page"' : '' ?>>Alerts</a>
                    </li>
                </ul>

                <p class="imreg-nav-group">Inquiries</p>
                <ul>
                    <li>
                        <a class="imreg-nav-link" href="<?= htmlspecialchars($urls['contacts'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $isActive(['Contacts']) ? 'aria-current="page"' : '' ?>>Contacts</a>
                    </li>
                    <li>
                        <a class="imreg-nav-link" href="<?= htmlspecialchars($urls['ojt'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $isActive(['OJT']) ? 'aria-current="page"' : '' ?>>OJT</a>
                    </li>
                    <li>
                        <a class="imreg-nav-link" href="<?= htmlspecialchars($urls['trainer'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $isActive(['Trainer']) ? 'aria-current="page"' : '' ?>>Trainers</a>
                    </li>
                    <li>
                        <a class="imreg-nav-link" href="<?= htmlspecialchars($urls['custom'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $actInq ? 'aria-current="page"' : '' ?>>Custom data</a>
                    </li>
                </ul>

                <p class="imreg-nav-group">Admin</p>
                <ul>
                    <li>
                        <a class="imreg-nav-link" href="<?= htmlspecialchars($urls['users'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $isActive(['Admin user', 'admin']) ? 'aria-current="page"' : '' ?>>Users</a>
                    </li>
                    <li>
                        <a class="imreg-nav-link" href="<?= htmlspecialchars($urls['settings'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $actAdm ? 'aria-current="page"' : '' ?>>Settings</a>
                    </li>
                </ul>

                <p class="imreg-nav-group">Utilities</p>
                <ul>
                    <li>
                        <a class="imreg-nav-link" href="<?= htmlspecialchars($urls['outbox'], ENT_QUOTES, 'UTF-8') ?>"
                            <?= $isActive(['Outbox']) ? 'aria-current="page"' : '' ?>>
                            Outbox
                            <?php if ($outboxQueued > 0): ?>
                                <span class="imreg-nav-link__badge" aria-label="<?= (int) $outboxQueued ?> queued"><?= (int) $outboxQueued ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
                        <a class="imreg-nav-link" href="<?= htmlspecialchars($urls['export_reg'], ENT_QUOTES, 'UTF-8') ?>">Export CSV</a>
                    </li>
                </ul>
            </nav>

            <div class="imreg-nav-user">
                <a class="imreg-nav-user__name" href="<?= htmlspecialchars($urls['profile'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?>
                </a>
                <div class="imreg-nav-user__role">My account</div>
                <form method="post" action="<?= htmlspecialchars($urls['logout'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="imreg-nav-user__logout">Log out</button>
                </form>
            </div>
        </aside>

        <main class="imreg-main" id="imreg-main" tabindex="-1">
            <header class="imreg-topbar">
                <div class="imreg-flex imreg-items-center imreg-gap-3">
                    <button type="button" class="imreg-menu-button" data-imreg-drawer-toggle aria-label="Open navigation">
                        <svg class="imreg-theme-toggle__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <line x1="3" y1="12" x2="21" y2="12"/>
                            <line x1="3" y1="18" x2="21" y2="18"/>
                        </svg>
                    </button>
                    <h1 class="imreg-topbar__title"><?= htmlspecialchars($__title !== '' ? $__title : 'IMedia Registration', ENT_QUOTES, 'UTF-8') ?></h1>
                </div>
                <div class="imreg-topbar__actions">
                    <button type="button" class="imreg-theme-toggle" data-imreg-theme-toggle
                            aria-label="Toggle dark mode" title="Toggle dark mode">
                        <svg class="imreg-theme-toggle__icon imreg-theme-toggle__moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                        </svg>
                        <svg class="imreg-theme-toggle__icon imreg-theme-toggle__sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="4"/>
                            <line x1="12" y1="2" x2="12" y2="4"/>
                            <line x1="12" y1="20" x2="12" y2="22"/>
                            <line x1="4.93" y1="4.93" x2="6.34" y2="6.34"/>
                            <line x1="17.66" y1="17.66" x2="19.07" y2="19.07"/>
                            <line x1="2" y1="12" x2="4" y2="12"/>
                            <line x1="20" y1="12" x2="22" y2="12"/>
                            <line x1="4.93" y1="19.07" x2="6.34" y2="17.66"/>
                            <line x1="17.66" y1="6.34" x2="19.07" y2="4.93"/>
                        </svg>
                    </button>
                </div>
            </header>
            <?php if (is_string($flash) && $flash !== ''): ?>
                <div class="imreg-flash imreg-flash--success" role="status" aria-live="polite">
                    <?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            <?= $__content /* raw HTML emitted by the inner view */ ?>
        </main>
    </div>
    <script src="<?= htmlspecialchars($baseUrl . '/assets/js/chart.umd.min.js', ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <script src="<?= htmlspecialchars($baseUrl . '/assets/js/app.js', ENT_QUOTES, 'UTF-8') ?>" defer></script>
</body>
</html>

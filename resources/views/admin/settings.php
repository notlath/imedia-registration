<?php
/**
 * Settings page view.
 *
 * Phase 6: rewritten on the design system. The 6-section structure is
 * preserved, but the markup now uses .imreg-section / .imreg-field /
 * .imreg-input / .imreg-btn instead of inline styles.
 *
 * @var string  $baseUrl
 * @var array   $settings
 * @var array   $routes
 * @var string  $csrf
 * @var ?string $flash
 * @var ?string $flashErr
 * @var array   $errors
 * @var ?string $errorMsg
 */
$baseUrl   = (string) ($baseUrl ?? '');
$settings  = is_array($settings ?? null) ? $settings : [];
$routes    = is_array($routes ?? null) ? $routes : [];
$csrf      = (string) ($csrf ?? '');
$errors    = is_array($errors ?? null) ? $errors : [];
$errorMsg  = $errorMsg ?? null;

$get = static function (string $key, mixed $default = '') use ($settings): mixed {
    return array_key_exists($key, $settings) && $settings[$key] !== null
        ? (is_bool($settings[$key]) ? (int) $settings[$key] : $settings[$key])
        : $default;
};
$val   = static fn (string $k, $d = '') => htmlspecialchars((string) $get($k, $d), ENT_QUOTES, 'UTF-8');
$bool  = static fn (string $k) => ((int) $get($k, 0)) === 1;
$errClass = static function (?string $key) use ($errors): string {
    return isset($errors[$key]) ? ' imreg-input--error' : '';
};
?>
<?php if (is_string($flash) && $flash !== ''): ?>
    <div class="imreg-flash imreg-flash--success" role="status" aria-live="polite"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (is_string($flashErr) && $flashErr !== ''): ?>
    <div class="imreg-flash imreg-flash--error" role="alert" aria-live="assertive"><?= htmlspecialchars($flashErr, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if (is_string($errorMsg) && $errorMsg !== ''): ?>
    <div class="imreg-flash imreg-flash--error" role="alert" aria-live="assertive"><?= htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" action="<?= htmlspecialchars($baseUrl . '/admin/settings', ENT_QUOTES, 'UTF-8') ?>" novalidate class="imreg-section" style="max-width:920px;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

    <?php $sec = 0; ?>

    <div class="imreg-section__header">
        <div class="imreg-section__num"><?= ++$sec ?></div>
        <div class="imreg-section__heading">
            <h2 class="imreg-section__title">General</h2>
            <p class="imreg-section__hint">Site identity and the alert threshold.</p>
        </div>
    </div>
    <div class="imreg-section__body">
        <div class="imreg-grid--form">
            <div class="imreg-field">
                <label for="site_name" class="imreg-label">Site name</label>
                <input id="site_name" name="site_name" required value="<?= $val('site_name', 'Inventive Media Registration') ?>" class="imreg-input<?= $errClass('site_name') ?>">
                <?php if (isset($errors['site_name'])): ?>
                    <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['site_name'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
            <div class="imreg-field">
                <label for="alert_threshold" class="imreg-label">Alert threshold <span class="imreg-text-muted" style="font-weight:400;">(confirmed students per slot)</span></label>
                <input id="alert_threshold" name="alert_threshold" type="number" min="1" max="9999" required value="<?= $val('alert_threshold', 9) ?>" class="imreg-input<?= $errClass('alert_threshold') ?>">
                <?php if (isset($errors['alert_threshold'])): ?>
                    <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['alert_threshold'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="imreg-section__header">
        <div class="imreg-section__num"><?= ++$sec ?></div>
        <div class="imreg-section__heading">
            <h2 class="imreg-section__title">Registration email</h2>
            <p class="imreg-section__hint">The email sent to a student after they submit a registration.</p>
        </div>
    </div>
    <div class="imreg-section__body">
        <div class="imreg-field imreg-mb-4">
            <label class="imreg-flex imreg-items-center imreg-gap-2" style="font-size:0.875rem;">
                <input type="checkbox" name="email_template_enabled" value="1" class="imreg-checkbox" <?= $bool('email_template_enabled') ? 'checked' : '' ?>>
                <span>Enabled</span>
            </label>
        </div>
        <div class="imreg-field imreg-mb-4">
            <label for="email_template_subject" class="imreg-label">Subject</label>
            <input id="email_template_subject" name="email_template_subject" value="<?= $val('email_template_subject', 'Welcome to {{course}} — Registration Confirmed!') ?>" class="imreg-input">
            <div class="imreg-help">Available tokens: <code>{{name}}</code>, <code>{{email}}</code>, <code>{{course}}</code>, <code>{{startDate}}</code>, <code>{{endDate}}</code>, <code>{{mobile}}</code>, <code>{{address}}</code></div>
        </div>
        <div class="imreg-field">
            <label for="email_template_body" class="imreg-label">Body (HTML)</label>
            <textarea id="email_template_body" name="email_template_body" rows="8" class="imreg-textarea imreg-input--code"><?= $val('email_template_body') ?></textarea>
        </div>
    </div>

    <div class="imreg-section__header">
        <div class="imreg-section__num"><?= ++$sec ?></div>
        <div class="imreg-section__heading">
            <h2 class="imreg-section__title">Admin notification</h2>
            <p class="imreg-section__hint">A copy of new submissions sent to the admin email.</p>
        </div>
    </div>
    <div class="imreg-section__body">
        <div class="imreg-field imreg-mb-4">
            <label class="imreg-flex imreg-items-center imreg-gap-2" style="font-size:0.875rem;">
                <input type="checkbox" name="admin_notification_enabled" value="1" class="imreg-checkbox" <?= $bool('admin_notification_enabled') ? 'checked' : '' ?>>
                <span>Send a copy of every new submission to the admin email</span>
            </label>
        </div>
        <div class="imreg-grid--form imreg-mb-4">
            <div class="imreg-field">
                <label for="admin_notification_to" class="imreg-label">To email</label>
                <input id="admin_notification_to" name="admin_notification_to" type="email" value="<?= $val('admin_notification_to') ?>" class="imreg-input<?= $errClass('admin_notification_to') ?>">
                <?php if (isset($errors['admin_notification_to'])): ?>
                    <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['admin_notification_to'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="imreg-field imreg-mb-4">
            <label for="admin_notification_subject" class="imreg-label">Subject</label>
            <input id="admin_notification_subject" name="admin_notification_subject" value="<?= $val('admin_notification_subject', 'New Registration: {{name}} — {{course}}') ?>" class="imreg-input">
        </div>
        <div class="imreg-field">
            <label for="admin_notification_body" class="imreg-label">Body (HTML)</label>
            <textarea id="admin_notification_body" name="admin_notification_body" rows="6" class="imreg-textarea imreg-input--code"><?= $val('admin_notification_body') ?></textarea>
        </div>
    </div>

    <div class="imreg-section__header">
        <div class="imreg-section__num"><?= ++$sec ?></div>
        <div class="imreg-section__heading">
            <h2 class="imreg-section__title">Threshold alert</h2>
            <p class="imreg-section__hint">Sent when a (course, year, month) slot hits the alert threshold. The threshold_alerts_sent UNIQUE KEY blocks duplicate alerts.</p>
        </div>
    </div>
    <div class="imreg-section__body">
        <div class="imreg-field imreg-mb-4">
            <label class="imreg-flex imreg-items-center imreg-gap-2" style="font-size:0.875rem;">
                <input type="checkbox" name="threshold_alert_enabled" value="1" class="imreg-checkbox" <?= $bool('threshold_alert_enabled') ? 'checked' : '' ?>>
                <span>Send an email when a (course, year, month) slot hits the threshold</span>
            </label>
        </div>
        <div class="imreg-grid--form imreg-mb-4">
            <div class="imreg-field">
                <label for="threshold_alert_to" class="imreg-label">To email</label>
                <input id="threshold_alert_to" name="threshold_alert_to" type="email" value="<?= $val('threshold_alert_to') ?>" class="imreg-input<?= $errClass('threshold_alert_to') ?>">
                <?php if (isset($errors['threshold_alert_to'])): ?>
                    <div class="imreg-error" role="alert"><?= htmlspecialchars($errors['threshold_alert_to'], ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="imreg-field imreg-mb-4">
            <label for="threshold_alert_subject" class="imreg-label">Subject</label>
            <input id="threshold_alert_subject" name="threshold_alert_subject" value="<?= $val('threshold_alert_subject', 'Course Capacity Reached: {{course}} ({{monthName}} {{year}})') ?>" class="imreg-input">
            <div class="imreg-help">Available tokens: <code>{{course}}</code>, <code>{{year}}</code>, <code>{{month}}</code>, <code>{{monthName}}</code>, <code>{{count}}</code>, <code>{{threshold}}</code></div>
        </div>
        <div class="imreg-field">
            <label for="threshold_alert_body" class="imreg-label">Body (HTML)</label>
            <textarea id="threshold_alert_body" name="threshold_alert_body" rows="6" class="imreg-textarea imreg-input--code"><?= $val('threshold_alert_body') ?></textarea>
        </div>
    </div>

    <div class="imreg-section__header">
        <div class="imreg-section__num"><?= ++$sec ?></div>
        <div class="imreg-section__heading">
            <h2 class="imreg-section__title">SMTP</h2>
            <p class="imreg-section__hint">Connection details for outgoing mail. Passwords and the HMAC shared secret are <strong>write-only</strong> — leave blank to keep the stored value.</p>
        </div>
    </div>
    <div class="imreg-section__body">
        <div class="imreg-grid--form">
            <div class="imreg-field">
                <label for="smtp_host" class="imreg-label">Host</label>
                <input id="smtp_host" name="smtp_host" value="<?= $val('smtp_host') ?>" placeholder="smtp.example.com" class="imreg-input">
            </div>
            <div class="imreg-field">
                <label for="smtp_port" class="imreg-label">Port</label>
                <input id="smtp_port" name="smtp_port" type="number" min="1" max="65535" value="<?= $val('smtp_port', 587) ?>" class="imreg-input">
            </div>
            <div class="imreg-field">
                <label for="smtp_user" class="imreg-label">Username</label>
                <input id="smtp_user" name="smtp_user" value="<?= $val('smtp_user') ?>" class="imreg-input">
            </div>
            <div class="imreg-field">
                <label for="smtp_pass" class="imreg-label">Password <span class="imreg-text-muted" style="font-weight:400;">(write-only)</span></label>
                <input id="smtp_pass" name="smtp_pass" type="password" autocomplete="new-password" placeholder="(unchanged)" class="imreg-input">
            </div>
            <div class="imreg-field">
                <label for="smtp_from_name" class="imreg-label">From name</label>
                <input id="smtp_from_name" name="smtp_from_name" value="<?= $val('smtp_from_name', 'Inventive Media Registration') ?>" class="imreg-input">
            </div>
            <div class="imreg-field">
                <label for="smtp_from_email" class="imreg-label">From email</label>
                <input id="smtp_from_email" name="smtp_from_email" type="email" value="<?= $val('smtp_from_email') ?>" placeholder="noreply@example.com" class="imreg-input">
            </div>
            <div class="imreg-field imreg-field--full">
                <label class="imreg-flex imreg-items-center imreg-gap-2" style="font-size:0.875rem;">
                    <input type="checkbox" name="smtp_secure" value="1" class="imreg-checkbox" <?= $bool('smtp_secure') ? 'checked' : '' ?>>
                    <span>Use TLS (smtp_secure)</span>
                </label>
            </div>
            <div class="imreg-field imreg-field--full">
                <label for="hmac_shared_secret" class="imreg-label">HMAC shared secret <span class="imreg-text-muted" style="font-weight:400;">(write-only)</span></label>
                <input id="hmac_shared_secret" name="hmac_shared_secret" type="password" autocomplete="new-password" placeholder="(unchanged)" class="imreg-input imreg-input--code">
                <div class="imreg-help">Must match the value in the WordPress plugin's IMedia Registration → Settings → Shared Secret.</div>
            </div>
        </div>
    </div>

    <div style="padding:1rem 1.5rem;background:var(--color-surface-container-low);border-top:1px solid var(--color-outline-variant);display:flex;justify-content:space-between;align-items:center;gap:0.5rem;flex-wrap:wrap;border-radius:0 0 var(--radius-lg) var(--radius-lg);">
        <a href="<?= htmlspecialchars($baseUrl . '/admin/outbox', ENT_QUOTES, 'UTF-8') ?>" class="imreg-btn imreg-btn--ghost">View outbox</a>
        <button type="submit" class="imreg-btn imreg-btn--primary">Save settings</button>
    </div>
</form>

<section class="imreg-section" style="max-width:920px;">
    <div class="imreg-section__header">
        <div class="imreg-section__num"><?= ++$sec ?></div>
        <div class="imreg-section__heading">
            <h2 class="imreg-section__title">Form routes</h2>
            <p class="imreg-section__hint">Map a WordPress form_id to a target table. The new app uses this on every /api/submit hit. Delete and re-add to change.</p>
        </div>
    </div>
    <div class="imreg-section__body">
        <?php
        $columns = ['Form ID', 'Target type', 'Target slug', ''];
        $rowKeys = ['form_id', 'target_type', 'target_slug', '__actions'];
        $cellCb = [null, null, null,
            static function ($r) use ($baseUrl, $csrf) {
                $id = (int) $r['form_id'];
                return '<form method="post" action="' . htmlspecialchars($baseUrl . '/admin/form-routes/delete', ENT_QUOTES, 'UTF-8') . '" style="display:inline" data-imreg-confirm="Remove this route?">'
                     . '<input type="hidden" name="_csrf" value="' . htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') . '">'
                     . '<input type="hidden" name="form_id" value="' . $id . '">'
                     . '<button type="submit" class="imreg-btn imreg-btn--danger imreg-btn--sm">Remove</button>'
                     . '</form>';
            },
        ];
        $rowsTbl = array_map(static fn ($r) => [
            'form_id'     => (int) $r['form_id'],
            'target_type' => (string) $r['target_type'],
            'target_slug' => (string) ($r['target_slug'] ?? ''),
            '__actions'   => $r,
        ], $routes);
        $empty = 'No routes configured yet.';
        include IMREG_VIEWS_PATH . '/partials/table.php';
        ?>

        <h3 class="imreg-text-display imreg-mt-6" style="font-size:0.9375rem;margin-bottom:0.75rem;">Add route</h3>
        <form method="post" action="<?= htmlspecialchars($baseUrl . '/admin/form-routes/add', ENT_QUOTES, 'UTF-8') ?>" class="imreg-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr)) auto;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <div class="imreg-field">
                <label for="fr-form_id" class="imreg-label">Form ID</label>
                <input id="fr-form_id" name="form_id" type="number" min="1" required class="imreg-input">
            </div>
            <div class="imreg-field">
                <label for="fr-target_type" class="imreg-label">Target type</label>
                <select id="fr-target_type" name="target_type" required class="imreg-select">
                    <option value="registration">registration</option>
                    <option value="contact">contact</option>
                    <option value="ojt">ojt</option>
                    <option value="trainer">trainer</option>
                    <option value="custom">custom (requires slug)</option>
                </select>
            </div>
            <div class="imreg-field">
                <label for="fr-target_slug" class="imreg-label">Target slug <span class="imreg-text-muted" style="font-weight:400;">(custom only)</span></label>
                <input id="fr-target_slug" name="target_slug" class="imreg-input">
            </div>
            <div class="imreg-field" style="justify-content:flex-end;">
                <button type="submit" class="imreg-btn imreg-btn--primary">Add route</button>
            </div>
        </form>
    </div>
</section>

<section class="imreg-section" style="max-width:920px;">
    <div class="imreg-section__header">
        <div class="imreg-section__num"><?= ++$sec ?></div>
        <div class="imreg-section__heading">
            <h2 class="imreg-section__title">Test email</h2>
            <p class="imreg-section__hint">Send a one-off test email to verify the SMTP setup. The email is enqueued in the outbox; the worker sends it.</p>
        </div>
    </div>
    <div class="imreg-section__body">
        <form method="post" action="<?= htmlspecialchars($baseUrl . '/admin/settings/test-email', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="imreg-btn imreg-btn--secondary">Send test email</button>
        </form>
    </div>
</section>

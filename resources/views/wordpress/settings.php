<?php
if (!defined('ABSPATH')) exit;

/**
 * Variables passed from IMF_Admin::render_settings_page().
 *
 * @var string $default_text
 * @var string $site_key
 * @var string $secret_key
 * @var string $app_url
 * @var bool   $app_secret_present  true if a secret is stored (we never echo the value)
 */
?>
<div class="imf-dashboard-wrap">
    <div class="imf-dashboard-header">
        <h1>Settings</h1>
    </div>

    <form method="post">
        <?php wp_nonce_field('imf_save_settings', 'imf_settings_nonce'); ?>
        <div class="imf-settings-card">
            <div class="imf-settings-card-header">
                <div class="imf-settings-card-title">
                    <h2>General Settings</h2>
                    <p>Configure default options for your forms</p>
                </div>
            </div>
            <div class="imf-settings-card-body">
                <div class="imf-settings-accordion open">
                    <div class="imf-settings-accordion-header">
                        General Settings Options
                        <svg class="imf-settings-accordion-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div class="imf-settings-accordion-body">
                        <div class="imf-settings-field">
                            <label>Default Submit Button Text</label>
                            <input type="text" name="imf_default_submit_text" value="<?php echo esc_attr($default_text); ?>" />
                            <p class="imf-settings-hint">The default text shown on submit buttons for new forms.</p>
                        </div>

                        <div class="imf-settings-field">
                            <label>reCAPTCHA Site Key</label>
                            <input type="text" name="imf_recaptcha_site_key" value="<?php echo esc_attr($site_key); ?>" />
                        </div>
                        <div class="imf-settings-field">
                            <label>reCAPTCHA Secret Key</label>
                            <input type="text" name="imf_recaptcha_secret_key" value="<?php echo esc_attr($secret_key); ?>" />
                        </div>
                    </div>
                </div>

                <div class="imf-settings-accordion">
                    <div class="imf-settings-accordion-header">
                        Standalone App (HMAC)
                        <svg class="imf-settings-accordion-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                    <div class="imf-settings-accordion-body">
                        <p class="imf-settings-hint" style="margin-top:0;">
                            Form submissions are forwarded to a standalone PHP app at <code>/registration/</code> on this site.
                            Each forwarded body is signed with HMAC-SHA256; the standalone app verifies the signature and rejects requests
                            older than 5 minutes (replay protection).
                        </p>

                        <div class="imf-settings-field">
                            <label for="imf_app_url">Registration App URL</label>
                            <input
                                type="url"
                                id="imf_app_url"
                                name="imf_app_url"
                                value="<?php echo esc_attr($app_url); ?>"
                                placeholder="https://www.inventivemedia.com.ph/registration"
                                autocomplete="off"
                                spellcheck="false" />
                            <p class="imf-settings-hint">
                                Base URL of the standalone app. Submissions are POSTed to <code>&lt;this&gt;/api/submit</code>.
                                If blank, the plugin falls back to <code><?php echo esc_html(trailingslashit(home_url())); ?>registration</code>.
                            </p>
                        </div>

                        <div class="imf-settings-field">
                            <label for="imf_shared_secret">Shared Secret</label>
                            <input
                                type="password"
                                id="imf_shared_secret"
                                name="imf_shared_secret"
                                value=""
                                placeholder="<?php echo $app_secret_present ? 'Stored (enter to replace)' : '(not set)'; ?>"
                                autocomplete="new-password"
                                spellcheck="false" />
                            <p class="imf-settings-hint">
                                Must match the value in the standalone app's <strong>Settings &rarr; SMTP &rarr; HMAC shared secret</strong>.
                                The value is <strong>write-only</strong>: it is never displayed back, and leaving this field blank keeps the stored value.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="imf-settings-actions">
                    <button type="submit" class="imf-settings-save-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                            <polyline points="17,21 17,13 7,13 7,21" />
                            <polyline points="7,3 7,8 15,8" />
                        </svg>
                        Save Settings
                    </button>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    document.querySelectorAll('.imf-settings-accordion-header').forEach(function(header) {
                        header.addEventListener('click', function() {
                            this.parentElement.classList.toggle('open');
                        });
                    });
                });
            </script>
        </div>

    </form>
</div>

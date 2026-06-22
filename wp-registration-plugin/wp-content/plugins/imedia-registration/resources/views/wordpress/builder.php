<?php
if (!defined('ABSPATH')) exit;

/**
 * Variables passed from IMF_Admin::render_builder().
 *
 * @var WP_Post $post
 * @var string  $fields_json
 * @var string  $api_url
 * @var string  $api_enabled
 * @var string  $form_title
 * @var string  $shortcode
 * @var array   $appearance
 * @var array   $email_settings
 */
?>
<div id="imf-app">
    <input type="hidden" id="imf_form_data_input" name="imf_form_data" value="<?php echo esc_attr($fields_json); ?>" />
    <input type="hidden" name="imf_api_endpoint" id="imf_api_endpoint_input" value="<?php echo esc_attr($api_url); ?>" />
    <input type="hidden" name="imf_api_enabled" id="imf_api_enabled_input" value="<?php echo esc_attr($api_enabled); ?>" />
    <input type="hidden" name="imf_appearance" id="imf_appearance_input" value="<?php echo esc_attr(json_encode($appearance)); ?>" />
    <input type="hidden" name="imf_email_settings" id="imf_email_settings_input" value="<?php echo esc_attr(json_encode($email_settings)); ?>" />

    <input type="hidden" name="post_status" value="publish" />
    <input type="hidden" name="publish" value="1" />

    <!-- TOP BAR -->
    <div class="imf-top-bar">
        <div class="imf-top-title">
            <input type="text" id="title" name="post_title" value="<?php echo esc_attr($form_title); ?>" placeholder="Enter form name..." style="border:none; font-size:17px; font-weight:700; font-family:'Inter',sans-serif; color:#1e293b; background:transparent; outline:none; width:260px;" />
            <?php if ($post->ID && $post->post_status === 'publish'): ?>
                <span class="imf-shortcode-tag" title="Click to copy"><?php echo esc_html($shortcode); ?></span>
            <?php endif; ?>
        </div>
        <div class="imf-top-actions">
            <button type="button" id="imf-btn-preview" class="imf-btn-preview">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>
                Preview
            </button>
            <button type="button" id="imf-btn-save" class="imf-btn-save">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                    <polyline points="17,21 17,13 7,13 7,21" />
                    <polyline points="7,3 7,8 15,8" />
                </svg>
                Save Form
            </button>
        </div>
    </div>

    <!-- MAIN LAYOUT -->
    <div class="imf-main-layout">
        <!-- CANVAS AREA -->
        <div class="imf-canvas-area">
            <div id="imf-canvas"></div>
            <div id="imf-canvas-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <path d="M12 8v8M8 12h8" />
                </svg>
                <p>Drag fields from the sidebar or click to add them to your form.</p>
                </div>
                </div>

                <!-- RESIZER -->
                <div class="imf-resizer" id="imf-resizer"></div>

                <!-- SIDEBAR -->
                <div class="imf-sidebar-area">
            <div class="imf-tabs">
                <button type="button" class="imf-tab active" data-tab="add">Add Fields</button>
                <button type="button" class="imf-tab" data-tab="settings">Field Settings</button>
                <button type="button" class="imf-tab" data-tab="emails">Emails</button>
                <button type="button" class="imf-tab" data-tab="form-settings">Settings</button>
            </div>

            <!-- TAB: Add Fields -->
            <div id="imf-tab-add" class="imf-tab-content active">
                <div class="imf-drag-instruction">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 16v-4M12 8h.01" />
                    </svg>
                    <span>Click or drag fields to add them to your form canvas.</span>
                </div>

                <!-- Standard Fields -->
                <div class="imf-field-group">
                    <h4>Standard Fields <svg class="imf-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9" />
                        </svg></h4>
                    <div class="imf-field-buttons">
                        <button type="button" class="imf-add-btn" data-type="text"><span class="imf-field-icon">━</span>Text</button>
                        <button type="button" class="imf-add-btn" data-type="textarea"><span class="imf-field-icon">☰</span>Paragraph</button>
                        <button type="button" class="imf-add-btn" data-type="select"><span class="imf-field-icon">▾</span>Drop Down</button>
                        <button type="button" class="imf-add-btn" data-type="number"><span class="imf-field-icon">#</span>Number</button>
                        <button type="button" class="imf-add-btn" data-type="checkbox"><span class="imf-field-icon">☑</span>Checkboxes</button>
                        <button type="button" class="imf-add-btn" data-type="radio"><span class="imf-field-icon">◉</span>Radio</button>
                        <button type="button" class="imf-add-btn" data-type="hidden"><span class="imf-field-icon">◌</span>Hidden</button>
                        <button type="button" class="imf-add-btn" data-type="section"><span class="imf-field-icon">—</span>Section</button>
                        <button type="button" class="imf-add-btn" data-type="multiple_choice"><span class="imf-field-icon">☷</span>Multi Choice</button>
                    </div>
                </div>

                <!-- Advanced Fields -->
                <div class="imf-field-group">
                    <h4>Advanced Fields <svg class="imf-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9" />
                        </svg></h4>
                    <div class="imf-field-buttons">
                        <button type="button" class="imf-add-btn" data-type="name"><span class="imf-field-icon">Aa</span>Name</button>
                        <button type="button" class="imf-add-btn" data-type="email"><span class="imf-field-icon">@</span>Email</button>
                        <button type="button" class="imf-add-btn" data-type="date"><span class="imf-field-icon">◫</span>Date</button>
                        <button type="button" class="imf-add-btn" data-type="time"><span class="imf-field-icon">◷</span>Time</button>
                        <button type="button" class="imf-add-btn" data-type="phone"><span class="imf-field-icon">☏</span>Phone</button>
                        <button type="button" class="imf-add-btn" data-type="address"><span class="imf-field-icon">⌂</span>Address</button>
                        <button type="button" class="imf-add-btn" data-type="file"><span class="imf-field-icon">⇪</span>File Upload</button>
                        <button type="button" class="imf-add-btn" data-type="multiselect"><span class="imf-field-icon">≡</span>Multiselect</button>
                    </div>
                </div>
            </div>

            <!-- TAB: Field Settings -->
            <div id="imf-tab-settings" class="imf-tab-content">
                <div id="imf-settings-empty" style="text-align:center; padding:40px 20px; color:#94a3b8;">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:12px;">
                        <circle cx="12" cy="12" r="3" />
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                    </svg>
                    <p>Select a field on the canvas to edit its settings.</p>
                </div>
                <div id="imf-settings-form" style="display:none;"></div>
            </div>

            <!-- TAB: Emails -->
            <div id="imf-tab-emails" class="imf-tab-content">
                <div class="imf-appearance-section">
                    <div class="imf-appearance-title">Admin Notification</div>
                    <div class="imf-setting-row imf-switch-wrap" style="margin-bottom:12px;">
                        <label class="imf-switch-label" style="margin:0; font-weight:600;">Enable Admin Notification</label>
                        <label class="imf-switch">
                            <input type="checkbox" id="imf-email-admin-enable" <?php checked($email_settings['admin_notify_enable'] ?? '0', '1'); ?> />
                            <span class="imf-switch-slider"></span>
                        </label>
                    </div>
                    <div id="imf-email-admin-config" style="display:<?php echo ($email_settings['admin_notify_enable'] ?? '0') === '1' ? 'block' : 'none'; ?>;">
                        <div class="imf-setting-row">
                            <label>Send To Email</label>
                            <input type="text" id="imf-email-admin-to" class="imf-setting-input" value="<?php echo esc_attr($email_settings['admin_notify_to']); ?>" placeholder="<?php echo get_option('admin_email'); ?>" />
                        </div>
                        <div class="imf-setting-row">
                            <label>Reply-To Email</label>
                            <input type="text" id="imf-email-admin-reply-to" class="imf-setting-input" value="<?php echo esc_attr($email_settings['admin_notify_reply_to'] ?? ''); ?>" placeholder="example@gmail.com" />
                        </div>
                        <div class="imf-setting-row">
                            <label>Subject</label>
                            <input type="text" id="imf-email-admin-subject" class="imf-setting-input" value="<?php echo esc_attr($email_settings['admin_notify_subject']); ?>" />
                        </div>
                        <div class="imf-setting-row">
                            <label>Message Body</label>
                            <div class="imf-rich-editor-wrap">
                                <?php wp_editor($email_settings['admin_notify_body'], 'imf_admin_body_editor', [
                                    'textarea_name' => 'admin_body_temp',
                                    'media_buttons' => false,
                                    'textarea_rows' => 8,
                                    'tinymce'       => [
                                        'resize' => 'vertical',
                                        'setup' => "function(ed) { ed.on('change keyup input', function() { window.syncEmailSettings(); }); }"
                                    ]
                                ]); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="imf-appearance-section" style="margin-top:24px;">
                    <div class="imf-appearance-title">User Confirmation</div>
                    <div class="imf-setting-row imf-switch-wrap" style="margin-bottom:12px;">
                        <label class="imf-switch-label" style="margin:0; font-weight:600;">Enable User Confirmation</label>
                        <label class="imf-switch">
                            <input type="checkbox" id="imf-email-user-enable" <?php checked($email_settings['user_confirm_enable'] ?? '0', '1'); ?> />
                            <span class="imf-switch-slider"></span>
                        </label>
                    </div>
                    <div id="imf-email-user-config" style="display:<?php echo ($email_settings['user_confirm_enable'] ?? '0') === '1' ? 'block' : 'none'; ?>;">
                        <div class="imf-setting-row">
                            <label>Subject</label>
                            <input type="text" id="imf-email-user-subject" class="imf-setting-input" value="<?php echo esc_attr($email_settings['user_confirm_subject']); ?>" />
                        </div>
                        <div class="imf-setting-row">
                            <label>Message Body</label>
                            <div class="imf-rich-editor-wrap">
                                <?php wp_editor($email_settings['user_confirm_body'], 'imf_user_body_editor', [
                                    'textarea_name' => 'user_body_temp',
                                    'media_buttons' => false,
                                    'textarea_rows' => 8,
                                    'tinymce'       => [
                                        'resize' => 'vertical',
                                        'setup' => "function(ed) { ed.on('change keyup input', function() { window.syncEmailSettings(); }); }"
                                    ]
                                ]); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="imf-appearance-section" style="margin-top:24px;">
                    <div class="imf-appearance-title">Available Merge Tags</div>
                    <div class="imf-setting-desc" style="margin-bottom:10px;">Copy and paste these tags into your subjects or message bodies.</div>
                    <div id="imf-email-tags-list" style="display:flex; flex-wrap:wrap; gap:6px;">
                        <code style="padding:4px 8px; background:#f1f5f9; border-radius:4px; font-size:12px; cursor:pointer;" title="Click to copy">[all_fields]</code>
                        <code style="padding:4px 8px; background:#f1f5f9; border-radius:4px; font-size:12px; cursor:pointer;" title="Click to copy">[form_title]</code>
                    </div>
                </div>
            </div>

            <!-- TAB: Form Settings (API + Appearance) -->
            <div id="imf-tab-form-settings" class="imf-tab-content">
                <!-- API Endpoint -->
                <div class="imf-api-endpoint-config">
                    <div class="imf-setting-row imf-switch-wrap" style="margin-bottom:12px;">
                        <label class="imf-switch-label" style="margin:0; font-weight:600;">Enable External API Forwarding</label>
                        <label class="imf-switch">
                            <input type="checkbox" id="imf-api-enabled-toggle" <?php checked($api_enabled, '1'); ?> />
                            <span class="imf-switch-slider"></span>
                        </label>
                    </div>
                    <div id="imf-api-endpoint-url-wrap" style="display:<?php echo $api_enabled === '1' ? 'block' : 'none'; ?>;">
                        <label>API Endpoint URL</label>
                        <input type="text" id="imf-api-url-input" placeholder="https://api.yourdomain.com/api/forms/submit" value="<?php echo esc_attr($api_url); ?>" />
                        <div class="imf-setting-desc" style="margin-top:6px;">The URL where form submissions will be sent via POST request.</div>
                    </div>
                </div>

                <!-- Form Appearance -->
                <div class="imf-appearance-section">
                    <div class="imf-appearance-title">Form Appearance</div>

                    <div class="imf-setting-row">
                        <label>Title Color</label>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="color" id="imf-appear-title-color" value="<?php echo esc_attr($appearance['title_color']); ?>" style="width:40px; height:36px; padding:2px; border:1px solid #e2e8f0; border-radius:6px; cursor:pointer;" />
                            <input type="text" id="imf-appear-title-color-text" class="imf-setting-input" value="<?php echo esc_attr($appearance['title_color']); ?>" style="flex:1;" />
                        </div>
                    </div>

                    <div class="imf-setting-row">
                        <label>Submit Button Background</label>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="color" id="imf-appear-submit-bg" value="<?php echo esc_attr($appearance['submit_bg_color']); ?>" style="width:40px; height:36px; padding:2px; border:1px solid #e2e8f0; border-radius:6px; cursor:pointer;" />
                            <input type="text" id="imf-appear-submit-bg-text" class="imf-setting-input" value="<?php echo esc_attr($appearance['submit_bg_color']); ?>" style="flex:1;" />
                        </div>
                    </div>

                    <div class="imf-setting-row">
                        <label>Submit Button Text Color</label>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <input type="color" id="imf-appear-submit-text" value="<?php echo esc_attr($appearance['submit_text_color']); ?>" style="width:40px; height:36px; padding:2px; border:1px solid #e2e8f0; border-radius:6px; cursor:pointer;" />
                            <input type="text" id="imf-appear-submit-text-text" class="imf-setting-input" value="<?php echo esc_attr($appearance['submit_text_color']); ?>" style="flex:1;" />
                        </div>
                    </div>

                    <div class="imf-setting-row">
                        <label>Submit Button Width</label>
                        <select id="imf-appear-submit-width" class="imf-setting-input">
                            <option value="auto" <?php selected($appearance['submit_width'], 'auto'); ?>>Auto</option>
                            <option value="full" <?php selected($appearance['submit_width'], 'full'); ?>>Full Width</option>
                        </select>
                    </div>

                    <div class="imf-setting-row">
                        <label>Submit Button Alignment</label>
                        <select id="imf-appear-submit-alignment" class="imf-setting-input">
                            <option value="left" <?php selected($appearance['submit_alignment'], 'left'); ?>>Left</option>
                            <option value="center" <?php selected($appearance['submit_alignment'], 'center'); ?>>Center</option>
                            <option value="right" <?php selected($appearance['submit_alignment'], 'right'); ?>>Right</option>
                        </select>
                    </div>

                    <div class="imf-setting-row imf-switch-wrap" style="margin-bottom:12px;">
                        <label class="imf-switch-label" style="margin:0;">Enable reCAPTCHA</label>
                        <label class="imf-switch">
                            <input type="checkbox" id="imf-appear-enable-recaptcha" <?php checked($appearance['enable_recaptcha'] ?? '0', '1'); ?> />
                            <span class="imf-switch-slider"></span>
                        </label>
                    </div>

                    <div class="imf-setting-row imf-switch-wrap" style="margin-bottom:12px;">
                        <label class="imf-switch-label" style="margin:0;">Enable Honeypot</label>
                        <label class="imf-switch">
                            <input type="checkbox" id="imf-appear-enable-honeypot" <?php checked($appearance['enable_honeypot'] ?? '0', '1'); ?> />
                            <span class="imf-switch-slider"></span>
                        </label>
                    </div>
                </div>

            </div>


        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div id="imf-delete-modal" class="imf-modal imf-delete-modal" style="display:none;">
        <div class="imf-modal-overlay"></div>
        <div class="imf-modal-content">
            <div class="imf-modal-body">
                <div class="imf-delete-warning">
                    <div class="imf-delete-warning-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            <line x1="12" y1="9" x2="12" y2="13" />
                            <line x1="12" y1="17" x2="12.01" y2="17" />
                        </svg>
                    </div>
                    <h3>Delete Field</h3>
                    <p>Are you sure you want to remove "<span class="imf-delete-field-name"></span>" from this form? This action cannot be undone.</p>
                    <div class="imf-delete-actions">
                        <button type="button" class="imf-btn-cancel">Cancel</button>
                        <button type="button" class="imf-btn-confirm-delete">Delete Field</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PREVIEW MODAL -->
    <div id="imf-preview-modal" class="imf-modal imf-preview-modal" style="display:none;">
        <div class="imf-modal-overlay"></div>
        <div class="imf-modal-content">
            <div class="imf-modal-header">
                <h2>Form Preview</h2>
                <button type="button" class="imf-modal-close">&times;</button>
            </div>
            <div class="imf-modal-body">
                <div class="imf-preview-card">
                    <h3 id="imf-live-preview-title" style="font-size:20px; font-weight:700; margin:0 0 20px;"></h3>
                    <div id="imf-live-preview-container" class="imf-preview-form-container"></div>
                    <div id="imf-live-preview-submit-wrap" style="margin-top:24px;">
                        <button type="button" id="imf-live-preview-submit-btn" style="padding:12px 32px; border:none; border-radius:10px; font-size:15px; font-weight:600; cursor:pointer; font-family:'Inter',sans-serif;">Submit</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        // Define sync function globally so TinyMCE can access it immediately
        window.syncEmailSettings = function() {
            var emailSettingsInput = document.getElementById('imf_email_settings_input');
            if (!emailSettingsInput) return;
            
            // Force TinyMCE to sync content back to textareas
            if (typeof tinymce !== 'undefined') {
                if (tinymce.get('imf_admin_body_editor')) tinymce.get('imf_admin_body_editor').save();
                if (tinymce.get('imf_user_body_editor')) tinymce.get('imf_user_body_editor').save();
            }
            
            var adminBody = document.getElementById('imf_admin_body_editor');
            var userBody = document.getElementById('imf_user_body_editor');
            var adminEnableEl = document.getElementById('imf-email-admin-enable');
            var userEnableEl = document.getElementById('imf-email-user-enable');
            
            var data = {
                admin_notify_enable: (adminEnableEl && adminEnableEl.checked) ? '1' : '0',
                admin_notify_to: document.getElementById('imf-email-admin-to') ? document.getElementById('imf-email-admin-to').value : '',
                admin_notify_reply_to: document.getElementById('imf-email-admin-reply-to') ? document.getElementById('imf-email-admin-reply-to').value : '',
                admin_notify_subject: document.getElementById('imf-email-admin-subject') ? document.getElementById('imf-email-admin-subject').value : '',
                admin_notify_body: adminBody ? adminBody.value : '',
                user_confirm_enable: (userEnableEl && userEnableEl.checked) ? '1' : '0',
                user_confirm_subject: document.getElementById('imf-email-user-subject') ? document.getElementById('imf-email-user-subject').value : '',
                user_confirm_body: userBody ? userBody.value : '',
            };
            emailSettingsInput.value = JSON.stringify(data);
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Fix: Add event listeners to editor textareas for Code/Text mode sync
            var editorTextareas = ['imf_admin_body_editor', 'imf_user_body_editor'];
            editorTextareas.forEach(function(id) {
                var el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', window.syncEmailSettings);
                    el.addEventListener('change', window.syncEmailSettings);
                    // Force visible styling for the textarea in Code mode
                    el.style.color = '#1e293b';
                    el.style.background = '#fff';
                    el.style.display = 'block';
                }
            });

            // Force immediate display update for toggles
            var syncToggles = function() {
                var ae = document.getElementById('imf-email-admin-enable');
                var ue = document.getElementById('imf-email-user-enable');
                var ac = document.getElementById('imf-email-admin-config');
                var uc = document.getElementById('imf-email-user-config');
                if (ae && ac) ac.style.display = ae.checked ? 'block' : 'none';
                if (ue && uc) uc.style.display = ue.checked ? 'block' : 'none';
            };
            syncToggles();
            // Sync API URL and Enabled status
            var urlInput = document.getElementById('imf-api-url-input');
            var hiddenInput = document.getElementById('imf_api_endpoint_input');
            var enabledToggle = document.getElementById('imf-api-enabled-toggle');
            var hiddenEnabled = document.getElementById('imf_api_enabled_input');
            var urlWrap = document.getElementById('imf-api-endpoint-url-wrap');

            if (enabledToggle && hiddenEnabled) {
                enabledToggle.addEventListener('change', function() {
                    hiddenEnabled.value = this.checked ? '1' : '0';
                    if (urlWrap) {
                        urlWrap.style.display = this.checked ? 'block' : 'none';
                    }
                });
            }

            if (urlInput && hiddenInput) {
                urlInput.addEventListener('input', function() {
                    hiddenInput.value = this.value;
                });
            }

            // Sync Appearance settings
            var appearanceInput = document.getElementById('imf_appearance_input');

            function syncAppearance() {
                if (!appearanceInput) return;
                var data = {
                    title_color: document.getElementById('imf-appear-title-color').value,
                    submit_bg_color: document.getElementById('imf-appear-submit-bg').value,
                    submit_text_color: document.getElementById('imf-appear-submit-text').value,
                    submit_width: document.getElementById('imf-appear-submit-width').value,
                    submit_alignment: document.getElementById('imf-appear-submit-alignment').value,
                    enable_recaptcha: document.getElementById('imf-appear-enable-recaptcha') && document.getElementById('imf-appear-enable-recaptcha').checked ? '1' : '0',
                    enable_honeypot: document.getElementById('imf-appear-enable-honeypot') && document.getElementById('imf-appear-enable-honeypot').checked ? '1' : '0',
                };
                appearanceInput.value = JSON.stringify(data);
            }

            // Color picker syncs
            ['title-color', 'submit-bg', 'submit-text'].forEach(function(key) {
                var colorInput = document.getElementById('imf-appear-' + key);
                var textInput = document.getElementById('imf-appear-' + key + '-text');
                if (colorInput && textInput) {
                    colorInput.addEventListener('input', function() {
                        textInput.value = this.value;
                        syncAppearance();
                    });
                    textInput.addEventListener('input', function() {
                        colorInput.value = this.value;
                        syncAppearance();
                    });
                }
            });

            // Select syncs
            ['submit-width', 'submit-alignment'].forEach(function(key) {
                var el = document.getElementById('imf-appear-' + key);
                if (el) el.addEventListener('change', syncAppearance);
            });
            var recapEl = document.getElementById('imf-appear-enable-recaptcha');
            if (recapEl) recapEl.addEventListener('change', syncAppearance);
            var hpEl = document.getElementById('imf-appear-enable-honeypot');
            if (hpEl) hpEl.addEventListener('change', syncAppearance);

            // Sync Email settings toggles
            var adminConfigWrap = document.getElementById('imf-email-admin-config');
            var userConfigWrap = document.getElementById('imf-email-user-config');

            ['admin-enable', 'user-enable'].forEach(function(key) {
                var el = document.getElementById('imf-email-' + key);
                if (el) {
                    el.addEventListener('change', function() {
                        var target = (key === 'admin-enable') ? adminConfigWrap : userConfigWrap;
                        if (target) target.style.display = this.checked ? 'block' : 'none';
                        window.syncEmailSettings();
                    });
                }
            });

            ['admin-to', 'admin-reply-to', 'admin-subject', 'user-subject'].forEach(function(key) {
                var el = document.getElementById('imf-email-' + key);
                if (el) el.addEventListener('input', window.syncEmailSettings);
            });

            // Handle Save button sync
            var saveBtn = document.getElementById('imf-btn-save');
            if (saveBtn) {
                saveBtn.addEventListener('click', function() {
                    window.syncEmailSettings();
                }, true); // Capture phase to ensure it runs before form-builder.js listener
            }

            // Dynamic Merge Tags
            var formDataInput = document.getElementById('imf_form_data_input');
            var tagsList = document.getElementById('imf-email-tags-list');

            function renderMergeTags() {
                if (!formDataInput || !tagsList) return;
                var fields = [];
                try {
                    fields = JSON.parse(formDataInput.value) || [];
                } catch(e) {}

                var html = '<code style="padding:4px 8px; background:#f1f5f9; border-radius:4px; font-size:12px; cursor:pointer;" title="Click to copy">[all_fields]</code>';
                html += '<code style="padding:4px 8px; background:#f1f5f9; border-radius:4px; font-size:12px; cursor:pointer;" title="Click to copy">[form_title]</code>';

                fields.forEach(function(f) {
                    if (['section', 'hidden'].indexOf(f.type) === -1) {
                        var name = f.name || f.id;
                        html += '<code style="padding:4px 8px; background:#f1f5f9; border-radius:4px; font-size:12px; cursor:pointer;" title="Click to copy">[' + name + ']</code>';
                    }
                });

                tagsList.innerHTML = html;

                // Add click-to-copy
                tagsList.querySelectorAll('code').forEach(function(code) {
                    code.onclick = function() {
                        var text = this.innerText;
                        var temp = document.createElement('textarea');
                        document.body.appendChild(temp);
                        temp.value = text;
                        temp.select();
                        document.execCommand('copy');
                        document.body.removeChild(temp);
                        
                        var oldBg = this.style.background;
                        this.style.background = '#dcfce7';
                        setTimeout(() => { this.style.background = oldBg; }, 1000);
                    };
                });
            }

            // Observer for form data changes to update tags
            if (formDataInput) {
                var observer = new MutationObserver(function(mutations) {
                    renderMergeTags();
                });
                observer.observe(formDataInput, { attributes: true });
                renderMergeTags(); // Initial render
            }
            
            // Initial sync
            window.syncEmailSettings();

        });
    })();
</script>

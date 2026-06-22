<?php
if (!defined('ABSPATH')) exit;

/**
 * Variables passed from IMF_Admin::render_dashboard().
 *
 * @var WP_Post[] $forms
 * @var string    $status_filter
 * @var string    $search
 * @var string    $base_url
 * @var string    $add_new_url
 * @var int       $count_all
 * @var int       $count_active
 * @var int       $count_inactive
 */
?>
<div class="imf-dashboard-wrap">
    <div class="imf-dashboard-header">
        <h1>IMedia Registration</h1>
        <button type="button" id="imf-open-new-form-modal" class="imf-btn-add-new">
            <span class="dashicons dashicons-plus-alt2"></span> Add New
        </button>
    </div>

    <div class="imf-filter-tabs">
        <a href="<?php echo esc_url($base_url); ?>" class="imf-filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
            All <span class="imf-filter-count">(<?php echo $count_all; ?>)</span>
        </a>
        <a href="<?php echo esc_url($base_url . '&status=active'); ?>" class="imf-filter-tab <?php echo $status_filter === 'active' ? 'active' : ''; ?>">
            Active <span class="imf-filter-count">(<?php echo $count_active; ?>)</span>
        </a>
        <a href="<?php echo esc_url($base_url . '&status=inactive'); ?>" class="imf-filter-tab <?php echo $status_filter === 'inactive' ? 'active' : ''; ?>">
            Inactive <span class="imf-filter-count">(<?php echo $count_inactive; ?>)</span>
        </a>
    </div>

    <div class="imf-search-bar">
        <form method="get" action="<?php echo esc_url($base_url); ?>" style="display:flex;">
            <input type="hidden" name="page" value="imedia-forms" />
            <input type="text" name="s" placeholder="Search forms..." value="<?php echo esc_attr($search); ?>" />
            <button type="submit">Search</button>
        </form>
    </div>

    <?php if (empty($forms)): ?>
        <div class="imf-empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" />
                <path d="M9 12h6M12 9v6" />
            </svg>
            <h3>No forms yet</h3>
            <p>Create your first form to start collecting data.</p>
            <button type="button" id="imf-open-new-form-modal-empty" class="imf-btn-add-new">
                <span class="dashicons dashicons-plus-alt2"></span> Create Form
            </button>
        </div>
    <?php else: ?>
        <table class="imf-form-table imf-sortable">
            <thead>
                <tr>
                    <th style="width:60px;">Status</th>
                    <th data-sort>Form Name</th>
                    <th style="width:80px;" data-sort>ID</th>
                    <th style="width:80px;" data-sort>Fields</th>
                    <th style="width:80px;" data-sort>Entries</th>
                    <th style="width:200px;">Shortcode</th>
                    <th style="width:140px;" data-sort>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php
            global $wpdb;
            foreach ($forms as $form):
                    $form_status = get_post_meta($form->ID, Imedia_Forms::META_STATUS, true);
                    $is_active = ($form_status !== 'inactive');
                    $fields_data = json_decode(get_post_meta($form->ID, Imedia_Forms::META_FIELDS, true), true);
                    $field_count = is_array($fields_data) ? count($fields_data) : 0;
                    $edit_url = get_edit_post_link($form->ID);
                    $entries_url = admin_url('admin.php?page=imedia-forms-entries&form_id=' . $form->ID);
                    $entry_count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM " . IMF_Database::table_name() . " WHERE form_id = %d AND status = 'active'",
                        $form->ID
                    ));
                ?>
                    <tr data-form-id="<?php echo esc_attr($form->ID); ?>">
                        <td>
                            <button type="button" class="imf-status-badge <?php echo $is_active ? 'active' : 'inactive'; ?>" data-id="<?php echo esc_attr($form->ID); ?>">
                                <span class="imf-status-dot"></span>
                                <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                            </button>
                        </td>
                        <td class="imf-form-title">
                            <a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($form->post_title ?: '(no title)'); ?></a>
                            <div class="imf-row-actions">
                                <a href="<?php echo esc_url($edit_url); ?>">Edit</a>
                                <a href="<?php echo esc_url($entries_url); ?>">Entries</a>
                                <a href="#" class="trash imf-delete-form-btn" data-id="<?php echo esc_attr($form->ID); ?>" data-title="<?php echo esc_attr($form->post_title); ?>">Trash</a>
                            </div>
                        </td>
                        <td style="color:#94a3b8; font-family:'SF Mono',monospace; font-size:12px;">#<?php echo $form->ID; ?></td>
                        <td><?php echo $field_count; ?></td>
                        <td><a href="<?php echo esc_url($entries_url); ?>" class="imf-entries-count-link"><?php echo intval($entry_count); ?></a></td>
                        <td><code style="font-size:12px; background:#f1f5f9; padding:3px 8px; border-radius:4px; color:#475569;">[imedia_form id="<?php echo $form->ID; ?>"]</code></td>
                        <td style="color:#94a3b8; font-size:13px;"><?php echo get_the_date('M j, Y', $form); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="imf-table-footer">
            <span><?php echo count($forms); ?> form(s)</span>
        </div>
    <?php endif; ?>

    <!-- Dashboard Delete Modal -->
    <div id="imf-dashboard-delete-modal" class="imf-modal imf-dashboard-delete-modal" style="display:none;">
        <div class="imf-modal-overlay"></div>
        <div class="imf-modal-content">
            <div class="imf-modal-body">
                <div class="imf-delete-warning">
                    <div class="imf-delete-warning-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14zM10 11v6M14 11v6" />
                        </svg>
                    </div>
                    <h3>Delete Form</h3>
                    <p>Are you sure you want to delete "<span id="imf-dash-delete-name"></span>"? This action cannot be undone.</p>
                    <div class="imf-delete-actions">
                        <button type="button" class="imf-btn-cancel" id="imf-dash-cancel-delete">Cancel</button>
                        <button type="button" class="imf-btn-confirm-delete" id="imf-dash-confirm-delete">Delete Form</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Form Modal -->
    <div id="imf-new-form-modal" class="imf-new-form-modal" role="dialog" aria-modal="true" aria-labelledby="imf-new-form-modal-title">
        <div class="imf-new-form-backdrop" id="imf-new-form-backdrop"></div>
        <div class="imf-new-form-dialog">
            <div class="imf-new-form-header">
                <div class="imf-new-form-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="3" />
                        <path d="M12 8v8M8 12h8" />
                    </svg>
                </div>
                <h2 id="imf-new-form-modal-title">New Form</h2>
                <button type="button" class="imf-new-form-close" id="imf-new-form-close" aria-label="Close">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>
            <div class="imf-new-form-body">
                <label class="imf-new-form-label" for="imf-new-form-title-input">Form Name</label>
                <input
                    type="text"
                    id="imf-new-form-title-input"
                    class="imf-new-form-input"
                    placeholder="e.g. Contact Form"
                    maxlength="120"
                    autocomplete="off" />
                <p class="imf-new-form-hint">Give your form a clear, descriptive name.</p>
            </div>
            <div class="imf-new-form-footer">
                <button type="button" class="imf-new-form-btn-cancel" id="imf-new-form-btn-cancel">Cancel</button>
                <button type="button" class="imf-new-form-btn-create" id="imf-new-form-btn-create">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M5 12h14M12 5l7 7-7 7" />
                    </svg>
                    Create &amp; Edit
                </button>
            </div>
        </div>
    </div>
</div>

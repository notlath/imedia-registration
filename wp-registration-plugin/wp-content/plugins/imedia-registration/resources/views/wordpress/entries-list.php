<?php
if (!defined('ABSPATH')) exit;

/**
 * Variables passed from IMF_Admin::render_entries_page().
 *
 * @var WP_Post   $form
 * @var WP_Post[] $forms
 * @var int       $form_id
 * @var object[]  $entries
 * @var array     $all_fields
 * @var string[]  $visible_columns
 * @var string    $status_filter
 * @var string    $search
 * @var string    $base_url
 * @var string    $dashboard_url
 * @var int       $total
 * @var int       $total_pages
 * @var int       $paged
 * @var int       $count_all
 * @var int       $count_unread
 * @var int       $count_starred
 * @var int       $count_trash
 */
?>
<div class="imf-dashboard-wrap imf-entries-wrap">
    <div class="imf-entries-back">
        <a href="<?php echo esc_url($dashboard_url); ?>" class="imf-back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7" />
            </svg>
            Back to Forms
        </a>
    </div>

    <div class="imf-dashboard-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h1>
            <?php echo esc_html($form->post_title); ?> — Entries
            <span class="imf-entries-total-badge"><?php echo intval($count_all); ?></span>
        </h1>

        <div class="imf-header-actions" style="display: flex; gap: 15px; align-items: center;">
            <div class="imf-form-selector">
                <select id="imf-global-form-select" onchange="if(this.value) window.location.href=this.value;">
                    <option value="<?php echo esc_url(admin_url('admin.php?page=imedia-forms-all-entries')); ?>">All Forms</option>
                    <?php foreach ($forms as $f):
                        global $wpdb;
                        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . IMF_Database::table_name() . " WHERE form_id = %d AND status = 'active'", $f->ID));
                    ?>
                        <option value="<?php echo esc_url(admin_url('admin.php?page=imedia-forms-entries&form_id=' . $f->ID)); ?>" <?php selected($f->ID, $form_id); ?>>
                            <?php echo esc_html($f->post_title); ?> (<?php echo intval($count); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="imf-filter-tabs">
        <a href="<?php echo esc_url($base_url); ?>" class="imf-filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
            All <span class="imf-filter-count">(<?php echo $count_all; ?>)</span>
        </a>
        <a href="<?php echo esc_url($base_url . '&entry_status=unread'); ?>" class="imf-filter-tab <?php echo $status_filter === 'unread' ? 'active' : ''; ?>">
            Unread <span class="imf-filter-count">(<?php echo $count_unread; ?>)</span>
        </a>
        <a href="<?php echo esc_url($base_url . '&entry_status=starred'); ?>" class="imf-filter-tab <?php echo $status_filter === 'starred' ? 'active' : ''; ?>">
            Starred <span class="imf-filter-count">(<?php echo $count_starred; ?>)</span>
        </a>
        <a href="<?php echo esc_url($base_url . '&entry_status=trash'); ?>" class="imf-filter-tab <?php echo $status_filter === 'trash' ? 'active' : ''; ?>">
            Trash <span class="imf-filter-count">(<?php echo $count_trash; ?>)</span>
        </a>
    </div>

    <div class="imf-entries-toolbar">
        <div class="imf-bulk-actions">
            <input type="checkbox" id="imf-select-all-entries" />
            <select id="imf-bulk-action-select">
                <option value="">Bulk Actions</option>
                <option value="mark_read">Mark as Read</option>
                <option value="mark_unread">Mark as Unread</option>
                <option value="trash">Move to Trash</option>
                <?php if ($status_filter === 'trash'): ?>
                    <option value="restore">Restore</option>
                    <option value="delete_permanent">Delete Permanently</option>
                <?php endif; ?>
            </select>
            <button type="button" id="imf-apply-bulk" class="imf-btn-bulk" data-form-id="<?php echo $form_id; ?>">Apply</button>

            <div class="imf-column-editor-wrap">
                <button type="button" id="imf-btn-edit-columns" class="imf-btn-edit-columns">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="9" y1="3" x2="9" y2="21"></line>
                    </svg>
                    Edit Columns
                </button>
                <div id="imf-column-editor-popover" class="imf-column-editor-popover" style="display:none;" data-form-id="<?php echo $form_id; ?>">
                    <div class="imf-column-editor-header">
                        <strong>Visible Columns</strong>
                    </div>
                    <div class="imf-column-editor-list">
                        <?php foreach ($all_fields as $df):
                            $fname = $df['name'] ?? $df['id'];
                            $is_visible = in_array($fname, $visible_columns);
                        ?>
                            <label class="imf-column-checkbox-label">
                                <input type="checkbox" class="imf-col-toggle" value="<?php echo esc_attr($fname); ?>" <?php checked($is_visible); ?> />
                                <?php echo esc_html($df['label']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="imf-column-editor-footer">
                        <button type="button" id="imf-save-columns-btn" class="imf-btn-save-cols">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="imf-search-bar">
            <form method="get" action="<?php echo esc_url($base_url); ?>" style="display:flex;">
                <input type="hidden" name="page" value="imedia-forms-entries" />
                <input type="hidden" name="form_id" value="<?php echo $form_id; ?>" />
                <?php if ($status_filter !== 'all'): ?>
                    <input type="hidden" name="entry_status" value="<?php echo esc_attr($status_filter); ?>" />
                <?php endif; ?>
                <input type="text" name="s" placeholder="Search entries..." value="<?php echo esc_attr($search); ?>" />
                <button type="submit">Search</button>
            </form>
        </div>
    </div>

    <?php if (empty($entries)): ?>
        <div class="imf-empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 12h6M12 9v6" />
                <rect x="3" y="3" width="18" height="18" rx="2" />
            </svg>
            <h3>No entries found</h3>
            <p>No submissions match your current filter.</p>
        </div>
    <?php else: ?>
        <table class="imf-form-table imf-entries-table imf-sortable">
            <thead>
                <tr>
                    <th style="width:40px;"><input type="checkbox" id="imf-select-all-top" /></th>
                    <th style="width:40px;">★</th>
                    <?php foreach ($all_fields as $df):
                        $fname = $df['name'] ?? $df['id'];
                        $is_visible = in_array($fname, $visible_columns);
                    ?>
                        <th data-sort data-col-name="<?php echo esc_attr($fname); ?>" style="<?php echo $is_visible ? '' : 'display:none;'; ?>">
                            <?php echo esc_html($df['label']); ?>
                        </th>
                    <?php endforeach; ?>
                    <th style="width:160px;" data-sort>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry):
                    $data = imf_normalize_entry_data(json_decode($entry->fields_data, true));
                    $detail_url = admin_url('admin.php?page=imedia-forms-entry-detail&form_id=' . $form_id . '&entry_id=' . $entry->id);
                    $unread_class = $entry->is_read ? '' : 'imf-entry-unread';
                ?>
                    <tr class="<?php echo $unread_class; ?>" data-entry-id="<?php echo $entry->id; ?>">
                        <td><input type="checkbox" class="imf-entry-cb" value="<?php echo $entry->id; ?>" /></td>
                        <td>
                            <button type="button" class="imf-star-btn <?php echo $entry->is_starred ? 'starred' : ''; ?>" data-id="<?php echo $entry->id; ?>" title="Toggle star">
                                <?php echo $entry->is_starred ? '★' : '☆'; ?>
                            </button>
                        </td>
                        <?php
                        // Build a quick name→value lookup from the normalized data
                        $data_map = [];
                        foreach ($data as $d) {
                            $data_map[$d['name']] = $d['value'];
                        }

                        $first_visible = true;
                        foreach ($all_fields as $df):
                            $fname = $df['name'] ?? $df['id'];
                            $fval  = $data_map[$fname] ?? '';
                            if (is_array($fval)) $fval = implode(', ', $fval);

                            $is_visible = in_array($fname, $visible_columns);

                            $td_class = '';
                            if ($is_visible && $first_visible) {
                                $td_class = 'imf-form-title imf-first-visible-col';
                                $first_visible = false;
                            }
                        ?>
                            <td class="<?php echo esc_attr($td_class); ?>" data-col-name="<?php echo esc_attr($fname); ?>" style="<?php echo $is_visible ? '' : 'display:none;'; ?>">
                                <?php if ($td_class === 'imf-form-title imf-first-visible-col'): ?>
                                    <a href="<?php echo esc_url($detail_url); ?>"><?php echo esc_html($fval ?: '(empty)'); ?></a>
                                    <div class="imf-row-actions">
                                        <?php if ($status_filter === 'trash'): ?>
                                            <a href="#" class="imf-entry-restore-btn" data-id="<?php echo $entry->id; ?>">Restore</a>
                                            <a href="#" class="imf-entry-delete-permanent-btn trash" data-id="<?php echo $entry->id; ?>">Delete Permanently</a>
                                        <?php else: ?>
                                            <a href="<?php echo esc_url($detail_url); ?>">View</a>
                                            <a href="#" class="imf-entry-delete-btn trash" data-id="<?php echo $entry->id; ?>">Trash</a>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <?php echo esc_html(mb_strimwidth($fval, 0, 60, '...')); ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td style="color:#94a3b8; font-size:13px;">
                            <?php echo imf_format_entry_date($entry->created_at); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
            <div class="imf-table-footer">
                <span><?php echo intval($total); ?> entries</span>
                <div class="imf-pagination">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo esc_url(add_query_arg('paged', $i)); ?>"
                            class="imf-page-link <?php echo $i === $paged ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="imf-table-footer">
                <span><?php echo intval($total); ?> entries</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

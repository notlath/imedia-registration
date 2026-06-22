<?php
if (!defined('ABSPATH')) exit;

/**
 * Variables passed from IMF_Admin::render_all_entries_page().
 *
 * @var object[]  $entries
 * @var WP_Post[] $forms
 * @var array     $form_map
 * @var int       $total
 * @var int       $total_pages
 * @var int       $paged
 * @var string    $search
 * @var string    $base_url
 * @var string    $dashboard_url
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
            All Entries
            <span class="imf-entries-total-badge"><?php echo intval($total); ?></span>
        </h1>
        <div class="imf-form-selector">
            <select id="imf-global-form-select" onchange="if(this.value) window.location.href=this.value;">
                <option value="<?php echo esc_url(admin_url('admin.php?page=imedia-forms-all-entries')); ?>" selected>All Forms</option>
                <?php foreach ($forms as $f):
                    global $wpdb;
                    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . IMF_Database::table_name() . " WHERE form_id = %d AND status = 'active'", $f->ID));
                ?>
                    <option value="<?php echo esc_url(admin_url('admin.php?page=imedia-forms-entries&form_id=' . $f->ID)); ?>">
                        <?php echo esc_html($f->post_title); ?> (<?php echo intval($count); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="imf-entries-toolbar">
        <div class="imf-bulk-actions">
            <input type="checkbox" id="imf-select-all-entries" />
            <select id="imf-bulk-action-select">
                <option value="">Bulk Actions</option>
                <option value="mark_read">Mark as Read</option>
                <option value="mark_unread">Mark as Unread</option>
                <option value="trash">Move to Trash</option>
            </select>
            <button type="button" id="imf-apply-bulk" class="imf-btn-bulk" data-form-id="all">Apply</button>
        </div>
        <div class="imf-search-bar">
            <form method="get" action="<?php echo esc_url($base_url); ?>" style="display:flex;">
                <input type="hidden" name="page" value="imedia-forms-all-entries" />
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
                    <th data-sort>Form</th>
                    <th data-sort>Name</th>
                    <th style="width:160px;" data-sort>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry):
                    $data = imf_normalize_entry_data(json_decode($entry->fields_data, true));
                    $detail_url = admin_url('admin.php?page=imedia-forms-entry-detail&form_id=' . $entry->form_id . '&entry_id=' . $entry->id);
                    $unread_class = $entry->is_read ? '' : 'imf-entry-unread';
                    $form_name = $form_map[$entry->form_id] ?? 'Unknown Form';

                    $primary_data = '';
                    if (!empty($data)) {
                        $first_item = reset($data);
                        $primary_val = is_array($first_item) ? ($first_item['value'] ?? '') : '';
                        if (is_array($primary_val)) {
                            $primary_val = implode(', ', $primary_val);
                        }
                        $primary_data = mb_strimwidth((string) $primary_val, 0, 80, '...');
                    }
                ?>
                    <tr class="<?php echo $unread_class; ?>" data-entry-id="<?php echo $entry->id; ?>">
                        <td><input type="checkbox" class="imf-entry-cb" value="<?php echo $entry->id; ?>" /></td>
                        <td>
                            <button type="button" class="imf-star-btn <?php echo $entry->is_starred ? 'starred' : ''; ?>" data-id="<?php echo $entry->id; ?>" title="Toggle star">
                                <?php echo $entry->is_starred ? '★' : '☆'; ?>
                            </button>
                        </td>
                        <td>
                            <span style="font-weight: 500; color: #475569;"><?php echo esc_html($form_name); ?></span>
                        </td>
                        <td class="imf-form-title">
                            <a href="<?php echo esc_url($detail_url); ?>"><?php echo esc_html($primary_data ?: '(empty)'); ?></a>
                            <div class="imf-row-actions">
                                <a href="<?php echo esc_url($detail_url); ?>">View</a>
                                <a href="#" class="imf-entry-delete-btn trash" data-id="<?php echo $entry->id; ?>">Trash</a>
                            </div>
                        </td>
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

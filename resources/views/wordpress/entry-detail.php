<?php
if (!defined('ABSPATH')) exit;

/**
 * Variables passed from IMF_Admin::render_entry_detail_page().
 *
 * @var object   $entry
 * @var WP_Post  $form
 * @var int      $entry_id
 * @var int      $form_id
 * @var array    $data
 * @var string   $entries_url
 * @var int|null $prev_id
 * @var int|null $next_id
 */
?>
<div class="imf-dashboard-wrap imf-entry-detail-wrap">
    <div class="imf-entries-back">
        <a href="<?php echo esc_url($entries_url); ?>" class="imf-back-link">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7" />
            </svg>
            Back to Entries
        </a>
    </div>

    <div class="imf-detail-header">
        <div class="imf-detail-title-area">
            <h1>Entry #<?php echo $entry_id; ?></h1>
            <span class="imf-detail-form-name"><?php echo esc_html($form->post_title); ?></span>
        </div>
        <div class="imf-detail-nav">
            <?php if ($prev_id): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=imedia-forms-entry-detail&form_id=' . $form_id . '&entry_id=' . $prev_id)); ?>" class="imf-detail-nav-btn" title="Previous Entry">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                </a>
            <?php endif; ?>
            <?php if ($next_id): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=imedia-forms-entry-detail&form_id=' . $form_id . '&entry_id=' . $next_id)); ?>" class="imf-detail-nav-btn" title="Next Entry">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 18l6-6-6-6" />
                    </svg>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="imf-detail-card">
        <div class="imf-detail-card-header">
            <h2>Submission Details</h2>
            <div class="imf-detail-actions">
                <button type="button" class="imf-star-btn <?php echo $entry->is_starred ? 'starred' : ''; ?>" data-id="<?php echo $entry->id; ?>" title="Toggle star">
                    <?php echo $entry->is_starred ? '★' : '☆'; ?>
                </button>
            </div>
        </div>
        <div class="imf-detail-fields">
            <?php foreach ($data as $field): ?>
                <div class="imf-detail-field">
                    <div class="imf-detail-label"><?php echo esc_html($field['label']); ?></div>
                    <div class="imf-detail-value"><?php echo esc_html($field['value'] ?: '—'); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="imf-detail-meta-card">
        <h3>Entry Meta</h3>
        <div class="imf-detail-meta-grid">
            <div class="imf-detail-meta-item">
                <span class="imf-meta-label">Entry ID</span>
                <span class="imf-meta-value">#<?php echo $entry_id; ?></span>
            </div>
            <div class="imf-detail-meta-item">
                <span class="imf-meta-label">Submitted</span>
                <span class="imf-meta-value"><?php echo imf_format_entry_date($entry->created_at); ?></span>
            </div>
            <div class="imf-detail-meta-item">
                <span class="imf-meta-label">IP Address</span>
                <span class="imf-meta-value"><?php echo esc_html($entry->ip_address); ?></span>
            </div>
            <div class="imf-detail-meta-item">
                <span class="imf-meta-label">User Agent</span>
                <span class="imf-meta-value imf-meta-ua"><?php echo esc_html($entry->user_agent); ?></span>
            </div>
        </div>
    </div>
</div>

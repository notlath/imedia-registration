<?php
if (!defined('ABSPATH')) exit;

class IMF_Database {
    public static function create_entries_table() {
        global $wpdb;
        $table = $wpdb->prefix . Imedia_Forms::ENTRIES_TABLE;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id BIGINT(20) UNSIGNED NOT NULL,
            fields_data LONGTEXT NOT NULL,
            ip_address VARCHAR(100) DEFAULT '',
            user_agent TEXT,
            status VARCHAR(20) DEFAULT 'active',
            is_read TINYINT(1) DEFAULT 0,
            is_starred TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY form_id (form_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . Imedia_Forms::ENTRIES_TABLE;
    }
}

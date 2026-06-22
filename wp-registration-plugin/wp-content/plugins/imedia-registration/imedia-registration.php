<?php

/**
 * Plugin Name: IMedia Registration
 * Plugin URI:  https://www.inventivemedia.com.ph/
 * Description: Drag-and-drop form builder for IMedia WordPress. Form submissions are forwarded to a standalone PHP app hosted at /imedia-registration/ on this domain.
 * Version:     3.0.0
 * Author:      Christian Catuday
 * License:     GPL v2 or later
 * Text Domain: imedia-registration
 */

if (! defined('ABSPATH')) exit;

class Imedia_Forms
{
    const VERSION = '3.0.0';
    const CPT = 'imedia_form';
    const META_FIELDS = '_imf_form_fields';
    const META_API_URL = '_imf_api_endpoint';
    const META_STATUS = '_imf_form_status';
    const META_APPEARANCE = '_imf_form_appearance';
    const META_EMAIL_SETTINGS = '_imf_email_settings';
    const META_API_ENABLED = '_imf_api_enabled';
    const ENTRIES_TABLE = 'imf_entries';

    public static function plugin_dir()
    {
        return plugin_dir_path(__FILE__);
    }

    public static function plugin_url()
    {
        return plugin_dir_url(__FILE__);
    }

    public function __construct()
    {
        $this->includes();

        add_action('init', [$this, 'register_cpt']);
        add_action('before_delete_post', [$this, 'delete_form_entries']);

        // Initialize modules
        new IMF_Database();
        new IMF_Admin();
        new IMF_Ajax();
        new IMF_Rest_API();
        new IMF_Frontend();
    }

    private function includes()
    {
        require_once self::plugin_dir() . 'includes/helpers.php';
        require_once self::plugin_dir() . 'includes/class-imf-database.php';
        require_once self::plugin_dir() . 'includes/class-imf-email.php';
        require_once self::plugin_dir() . 'includes/class-imf-admin.php';
        require_once self::plugin_dir() . 'includes/class-imf-ajax.php';
        require_once self::plugin_dir() . 'includes/class-imf-rest-api.php';
        require_once self::plugin_dir() . 'includes/class-imf-frontend.php';
    }

    public function delete_form_entries($post_id)
    {
        if (get_post_type($post_id) === self::CPT) {
            global $wpdb;
            $table = IMF_Database::table_name();
            $wpdb->delete($table, ['form_id' => $post_id], ['%d']);
        }
    }

    public function register_cpt()
    {
        register_post_type(self::CPT, [
            'labels' => [
                'name'          => 'IMedia Registration',
                'singular_name' => 'Form',
                'add_new'       => 'Add New',
                'add_new_item'  => 'Add New Form',
                'edit_item'     => 'Edit Form',
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'supports'            => ['title'],
        ]);
    }
}

// Activation hook — create DB table
register_activation_hook(__FILE__, ['IMF_Database', 'create_entries_table']);

// Initialize
new Imedia_Forms();

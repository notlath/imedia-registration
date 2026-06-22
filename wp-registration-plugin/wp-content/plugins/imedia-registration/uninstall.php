<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package IMedia_Forms
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Drop custom table
$table_name = $wpdb->prefix . 'imf_entries';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );

// Delete all imedia_form posts and their metadata
$forms = get_posts( array(
	'post_type'   => 'imedia_form',
	'numberposts' => -1,
	'post_status' => 'any',
) );

foreach ( $forms as $form ) {
	wp_delete_post( $form->ID, true );
}

// Delete options
delete_option( 'imf_default_submit_text' );
delete_option( 'imf_recaptcha_site_key' );
delete_option( 'imf_recaptcha_secret_key' );
delete_option( 'imf_admin_notify_enable' );
delete_option( 'imf_admin_notify_to' );
delete_option( 'imf_admin_notify_subject' );
delete_option( 'imf_admin_notify_body' );
delete_option( 'imf_user_confirm_enable' );
delete_option( 'imf_user_confirm_subject' );
delete_option( 'imf_user_confirm_body' );

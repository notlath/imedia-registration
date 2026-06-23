<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IMF_Ajax {
	public function __construct() {
		// Form AJAX handlers
		add_action( 'wp_ajax_imf_toggle_status', array( $this, 'ajax_toggle_status' ) );
		add_action( 'wp_ajax_imf_delete_form', array( $this, 'ajax_delete_form' ) );
		add_action( 'wp_ajax_imf_create_form', array( $this, 'ajax_create_form' ) );

		// Entry AJAX handlers
		add_action( 'wp_ajax_imf_delete_entry', array( $this, 'ajax_delete_entry' ) );
		add_action( 'wp_ajax_imf_bulk_entry_action', array( $this, 'ajax_bulk_entry_action' ) );
		add_action( 'wp_ajax_imf_toggle_star', array( $this, 'ajax_toggle_star' ) );
		add_action( 'wp_ajax_imf_toggle_read', array( $this, 'ajax_toggle_read' ) );
		add_action( 'wp_ajax_imf_save_column_prefs', array( $this, 'ajax_save_column_prefs' ) );

		// Export handler
		add_action( 'admin_post_imf_export_entries_csv', array( $this, 'export_entries_csv' ) );
	}

	/* ========================================
		AJAX — CREATE FORM (from modal)
		======================================== */
	public function ajax_create_form() {
		check_ajax_referer( 'imf_nonce', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
		if ( empty( $title ) ) {
			wp_send_json_error( array( 'message' => 'Title is required' ) );
		}

		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_type'   => Imedia_Forms::CPT,
				'post_status' => 'publish',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			wp_send_json_error( array( 'message' => $post_id->get_error_message() ) );
		}

		// Set default meta
		update_post_meta( $post_id, Imedia_Forms::META_STATUS, 'active' );
		update_post_meta( $post_id, Imedia_Forms::META_FIELDS, '[]' );

		wp_send_json_success(
			array(
				'post_id'  => $post_id,
				'edit_url' => admin_url( 'post.php?post=' . $post_id . '&action=edit' ),
			)
		);
	}

	/* ========================================
		AJAX — TOGGLE STATUS
		======================================== */
	public function ajax_toggle_status() {
		check_ajax_referer( 'imf_nonce', 'nonce' );
		$form_id = intval( $_POST['form_id'] );
		if ( ! $form_id || ! current_user_can( 'edit_post', $form_id ) ) {
			wp_send_json_error();
		}

		$current    = get_post_meta( $form_id, Imedia_Forms::META_STATUS, true );
		$new_status = ( $current === 'inactive' ) ? 'active' : 'inactive';
		update_post_meta( $form_id, Imedia_Forms::META_STATUS, $new_status );
		wp_send_json_success( array( 'status' => $new_status ) );
	}

	/* ========================================
		AJAX — DELETE FORM
		======================================== */
	public function ajax_delete_form() {
		check_ajax_referer( 'imf_nonce', 'nonce' );
		$form_id = intval( $_POST['form_id'] );
		if ( ! $form_id || ! current_user_can( 'delete_post', $form_id ) ) {
			wp_send_json_error();
		}

		wp_delete_post( $form_id, true );
		wp_send_json_success();
	}

	/* ========================================
		ENTRY AJAX HANDLERS
		======================================== */
	public function ajax_delete_entry() {
		check_ajax_referer( 'imf_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}
		global $wpdb;
		$id = intval( $_POST['entry_id'] );
		$wpdb->update( IMF_Database::table_name(), array( 'status' => 'trash' ), array( 'id' => $id ) );
		wp_send_json_success();
	}

	public function ajax_toggle_star() {
		check_ajax_referer( 'imf_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}
		global $wpdb;
		$table   = IMF_Database::table_name();
		$id      = intval( $_POST['entry_id'] );
		$current = $wpdb->get_var( $wpdb->prepare( "SELECT is_starred FROM $table WHERE id = %d", $id ) );
		$wpdb->update( $table, array( 'is_starred' => $current ? 0 : 1 ), array( 'id' => $id ) );
		wp_send_json_success( array( 'is_starred' => ! $current ) );
	}

	public function ajax_toggle_read() {
		check_ajax_referer( 'imf_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}
		global $wpdb;
		$table   = IMF_Database::table_name();
		$id      = intval( $_POST['entry_id'] );
		$current = $wpdb->get_var( $wpdb->prepare( "SELECT is_read FROM $table WHERE id = %d", $id ) );
		$wpdb->update( $table, array( 'is_read' => $current ? 0 : 1 ), array( 'id' => $id ) );
		wp_send_json_success( array( 'is_read' => ! $current ) );
	}

	public function ajax_bulk_entry_action() {
		check_ajax_referer( 'imf_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}
		global $wpdb;
		$table  = IMF_Database::table_name();
		$action = sanitize_text_field( $_POST['bulk_action'] );
		$ids    = array_map( 'intval', (array) ( $_POST['entry_ids'] ?? array() ) );
		if ( empty( $ids ) ) {
			wp_send_json_error();
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		switch ( $action ) {
			case 'trash':
				$wpdb->query( $wpdb->prepare( "UPDATE $table SET status = 'trash' WHERE id IN ($placeholders)", ...$ids ) );
				break;
			case 'restore':
				$wpdb->query( $wpdb->prepare( "UPDATE $table SET status = 'active' WHERE id IN ($placeholders)", ...$ids ) );
				break;
			case 'delete_permanent':
				$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE id IN ($placeholders)", ...$ids ) );
				break;
			case 'mark_read':
				$wpdb->query( $wpdb->prepare( "UPDATE $table SET is_read = 1 WHERE id IN ($placeholders)", ...$ids ) );
				break;
			case 'mark_unread':
				$wpdb->query( $wpdb->prepare( "UPDATE $table SET is_read = 0 WHERE id IN ($placeholders)", ...$ids ) );
				break;
		}

		wp_send_json_success();
	}

	public function ajax_save_column_prefs() {
		check_ajax_referer( 'imf_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		$form_id = intval( $_POST['form_id'] );
		$columns = isset( $_POST['columns'] ) ? (array) $_POST['columns'] : array();
		$columns = array_map( 'sanitize_text_field', $columns );

		$pref_key = '_imf_columns_' . $form_id;
		update_user_meta( get_current_user_id(), $pref_key, $columns );

		wp_send_json_success();
	}

	public function export_entries_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		$form_id = intval( $_GET['form_id'] ?? 0 );
		if ( ! $form_id ) {
			wp_die( 'Invalid form ID' );
		}

		global $wpdb;
		$table = IMF_Database::table_name();

		$status_filter = sanitize_text_field( $_GET['entry_status'] ?? 'all' );
		$search        = sanitize_text_field( $_GET['s'] ?? '' );

		$where = $wpdb->prepare( 'WHERE form_id = %d', $form_id );
		if ( $status_filter === 'starred' ) {
			$where .= " AND is_starred = 1 AND status = 'active'";
		} elseif ( $status_filter === 'unread' ) {
			$where .= " AND is_read = 0 AND status = 'active'";
		} elseif ( $status_filter === 'trash' ) {
			$where .= " AND status = 'trash'";
		} else {
			$where .= " AND status = 'active'";
		}

		if ( $search ) {
			$like   = '%' . $wpdb->esc_like( $search ) . '%';
			$where .= $wpdb->prepare( ' AND fields_data LIKE %s', $like );
		}

		$entries = $wpdb->get_results( "SELECT * FROM $table $where ORDER BY created_at DESC" );

		$form   = get_post( $form_id );
		$fields = json_decode( get_post_meta( $form_id, Imedia_Forms::META_FIELDS, true ), true ) ?: array();
		$header = array( 'Entry ID', 'Date', 'IP Address', 'User Agent' );

		$form_field_names = array();
		foreach ( $fields as $f ) {
			if ( in_array( $f['type'], array( 'section', 'hidden' ) ) ) {
				continue;
			}
			$header[]           = $f['label'];
			$form_field_names[] = $f['name'];
		}
		$header[] = 'Starred';
		$header[] = 'Status';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=form-' . $form_id . '-entries-' . wp_date( 'Y-m-d' ) . '.csv' );
		$output = fopen( 'php://output', 'w' );

		fputcsv( $output, $header );

		foreach ( $entries as $entry ) {
			$data_raw = imf_normalize_entry_data( json_decode( $entry->fields_data, true ) );
			// Build name→value map from normalized list
			$data = array();
			foreach ( $data_raw as $d ) {
				$data[ $d['name'] ] = $d['value'];
			}
			$row = array(
				$entry->id,
				$entry->created_at,
				$entry->ip_address,
				$entry->user_agent,
			);
			foreach ( $form_field_names as $name ) {
				$val = $data[ $name ] ?? '';
				if ( is_array( $val ) ) {
					$val = implode( ', ', $val );
				}
				$row[] = $val;
			}
			$row[] = $entry->is_starred ? 'Yes' : 'No';
			$row[] = $entry->status;
			fputcsv( $output, $row );
		}

		fclose( $output );
		exit;
	}
}

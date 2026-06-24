<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IMF_Rest_API {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	public function register_rest_routes() {
		register_rest_route(
			'imedia-forms/v1',
			'/submit',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_submit_form' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function rest_submit_form( $request ) {
		global $wpdb;
		$params = $request->get_params();
		if ( empty( $params ) ) {
			$params = $request->get_json_params() ?: array();
		}

		$form_id = intval( $params['_imf_form_id'] ?? 0 );
		if ( ! $form_id ) {
			return new WP_Error( 'invalid_form', 'Invalid Form ID', array( 'status' => 400 ) );
		}

		$post = get_post( $form_id );
		if ( ! $post || $post->post_type !== Imedia_Forms::CPT ) {
			return new WP_Error( 'not_found', 'Form not found', array( 'status' => 404 ) );
		}

		$fields_raw = get_post_meta( $form_id, Imedia_Forms::META_FIELDS, true );
		$fields     = json_decode( $fields_raw, true ) ?: array();

		$files = $request->get_file_params();
		if ( ! empty( $files ) ) {
			if ( ! function_exists( 'wp_handle_upload' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			foreach ( $files as $file_key => $file_data ) {
				if ( is_array( $file_data ) && empty( $file_data['error'] ) ) {
					$upload_overrides = array( 'test_form' => false );
					$uploaded_file    = wp_handle_upload( $file_data, $upload_overrides );
					if ( $uploaded_file && ! isset( $uploaded_file['error'] ) ) {
						$params[ $file_key ] = $uploaded_file['url'];
					}
				}
			}
		}

		$entry_data = array();
		foreach ( $fields as $f ) {
			if ( $f['type'] === 'section' || $f['type'] === 'hidden' ) {
				continue;
			}
			$name  = $f['name'] ?? $f['id'];
			$label = $f['label'];
			$val   = $params[ $name ] ?? '';
			if ( is_array( $val ) ) {
				$val = implode( ', ', $val );
			}

			if ( $f['type'] === 'name' ) {
				$val = trim( ( $params[ $name . '_first' ] ?? '' ) . ' ' . ( $params[ $name . '_last' ] ?? '' ) );
			} elseif ( $f['type'] === 'date' && ( $f['date_input_type'] ?? '' ) !== 'date_picker' ) {
				$val = ( $params[ $name . '_month' ] ?? '' ) . '/' . ( $params[ $name . '_day' ] ?? '' ) . '/' . ( $params[ $name . '_year' ] ?? '' );
			} elseif ( $f['type'] === 'address' ) {
				$val = trim( ( $params[ $name . '_street' ] ?? '' ) . ', ' . ( $params[ $name . '_street2' ] ?? '' ) . ', ' . ( $params[ $name . '_city' ] ?? '' ) . ' ' . ( $params[ $name . '_state' ] ?? '' ) . ' ' . ( $params[ $name . '_zip' ] ?? '' ) );
			}

			$val = imf_sanitize_field_value( $val, $f['type'] );

			$entry_data[] = array(
				'name'  => $name,
				'label' => $label,
				'value' => $val,
				'type'  => $f['type'],
			);
		}

		$table = IMF_Database::table_name();
		$wpdb->insert(
			$table,
			array(
				'form_id'     => $form_id,
				'fields_data' => wp_json_encode( $entry_data ),
				'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
				'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
				'status'      => 'active',
				'is_read'     => 0,
				'is_starred'  => 0,
				'created_at'  => current_time( 'mysql' ),
			)
		);
		$entry_id = $wpdb->insert_id;

		IMF_Email::send_from_request( $form_id, $fields, $params, array() );

		$api_enabled = get_post_meta( $form_id, Imedia_Forms::META_API_ENABLED, true );
		$api_url     = get_post_meta( $form_id, Imedia_Forms::META_API_URL, true );

		if ( $api_enabled === '1' ) {
			// Phase 7+: forward to the standalone app with HMAC signing.
			// Resolve the destination URL. Priority:
			//   1. Per-form META_API_URL override (legacy).
			//   2. Global imf_app_url option.
			//   3. site_url() + '/registration' (final fallback).
			$per_form_url = is_string( $api_url ) ? trim( $api_url ) : '';
			$global_url   = (string) get_option( 'imf_app_url', '' );
			$resolved_url = $per_form_url !== '' ? $per_form_url : (string) $global_url;
			if ( $resolved_url === '' ) {
				$resolved_url = trailingslashit( home_url() ) . 'registration';
			}
			$submit_url = rtrim( $resolved_url, '/' ) . '/api/submit';

			// ---- Field name mapping ----
			// Map form-builder field names to canonical names per
			// Submission Contract v1. The canonical list is in priority
			// order so exact matches always win over contains-matches.
			$canonical_names = array(
				'course', 'start_date', 'end_date', 'email', 'mobile',
				'address', 'subject', 'message', 'position', 'name',
			);

			$canonical_fields = array();
			$unmapped_fields  = array();
			$mapping_log      = array();

			foreach ( $entry_data as $ed ) {
				$name  = $ed['name'];
				$label = $ed['label'];
				$val   = $ed['value'];
				$type  = $ed['type'];

				$resolved = imf_resolve_canonical(
					array(
						'name'           => $name,
						'label'          => $label,
						'canonical_name' => $ed['canonical_name'] ?? '',
					),
					$canonical_names
				);

				$canonical = $resolved['canonical'];
				$source    = $resolved['source'];

				if ( $canonical === null ) {
					$unmapped_fields[ $name ] = $val;
					$mapping_log[]            = "$name->(null)";
					continue;
				}

				// Collision detection — keep first, log warning.
				if ( array_key_exists( $canonical, $canonical_fields ) ) {
					error_log(
						sprintf(
							'[IMF] Canonical collision on form_id=%d: "%s" already set; '
							. 'keeping first, ignoring "%s" from field "%s"',
							$form_id,
							$canonical,
							(string) $val,
							$label
						)
					);
					$mapping_log[] = "$name->{$canonical}(collision)";
					continue;
				}

				// Log label-inference discoveries so admins can migrate.
				if ( $source === 'label_exact' || $source === 'label_contains' ) {
					error_log(
						sprintf(
							'[IMF] Canonical resolved via label inference: form_id=%d field="%s" label="%s" -> "%s" (source=%s)',
							$form_id,
							$name,
							$label,
							$canonical,
							$source
						)
					);
				}

				// Conditional date normalization.
				if ( $type === 'date' && in_array( $canonical, array( 'start_date', 'end_date' ), true ) ) {
					$val = imf_normalize_date( (string) $val );
				}

				$canonical_fields[ $canonical ] = $val;
				$mapping_log[]                  = "$name->{$canonical}($source)";
			}

			// Log a one-line summary per submission.
			error_log(
				sprintf(
					'[IMF] form_id=%d mappings: %s',
					$form_id,
					implode( ', ', $mapping_log )
				)
			);

			// ---- Build payload ----
			// The standalone app expects:
			//   { "form_id": <int>, "fields": { ... }, "_imf_timestamp": <int> }
			$payload = array(
				'form_id'        => (int) $form_id,
				'fields'         => array_merge( $canonical_fields, $unmapped_fields ),
				'_imf_timestamp' => (int) time(),
			);

			$body    = wp_json_encode( $payload );
			$secret  = (string) get_option( 'imf_shared_secret', '' );
			$headers = array(
				'Content-Type' => 'application/json; charset=utf-8',
				'Accept'       => 'application/json',
				'User-Agent'   => 'IMediaRegistration/' . Imedia_Forms::VERSION . ' (+' . home_url() . ')',
			);
			if ( $secret !== '' ) {
				$headers['X-IMF-Signature'] = imf_hmac_sign( $body, $secret );
			}

			// Best-effort forward. blocking => false so the visitor's
			// form response is not delayed by the standalone. The
			// local wp_imf_entries row is the safety net.
			$response = wp_remote_post(
				$submit_url,
				array(
					'headers'     => $headers,
					'body'        => $body,
					'timeout'     => 15,
					'blocking'    => false,
					'sslverify'   => true,
					'data_format' => 'body',
				)
			);

			if ( is_wp_error( $response ) ) {
				error_log(
					sprintf(
						'[IMF] Forward to standalone failed: %s (form_id=%d, url=%s)',
						$response->get_error_message(),
						(int) $form_id,
						$submit_url
					)
				);
			}
		}

		return rest_ensure_response(
			array(
				'success'  => true,
				'entry_id' => $entry_id,
			)
		);
	}
}

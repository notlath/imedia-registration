<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IMF_Email {
	public static function send_from_request( $form_id, $fields, $params, $attachments ) {
		$post = get_post( $form_id );
		if ( ! $post || $post->post_type !== Imedia_Forms::CPT ) {
			return false;
		}

		$email_settings = imf_get_email_settings_defaults( $form_id, $post->post_title );

		$admin_notify_enable  = $email_settings['admin_notify_enable'];
		$admin_notify_to      = $email_settings['admin_notify_to'];
		$admin_notify_subject = $email_settings['admin_notify_subject'];
		$admin_notify_body    = $email_settings['admin_notify_body'];

		$user_confirm_enable  = $email_settings['user_confirm_enable'];
		$user_confirm_subject = $email_settings['user_confirm_subject'];
		$user_confirm_body    = $email_settings['user_confirm_body'];

		// Build [all_fields] text and mapping
		$all_fields_text = '<div style="font-family: sans-serif; font-size: 14px; line-height: 1.6; color: #333;">';
		$field_map       = array();
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

			// Handle multi-part fields
			if ( $f['type'] === 'name' ) {
				$val = trim( ( $params[ $name . '_first' ] ?? '' ) . ' ' . ( $params[ $name . '_last' ] ?? '' ) );
			} elseif ( $f['type'] === 'date' && ( $f['date_input_type'] ?? '' ) !== 'date_picker' ) {
				$val = ( $params[ $name . '_month' ] ?? '' ) . '/' . ( $params[ $name . '_day' ] ?? '' ) . '/' . ( $params[ $name . '_year' ] ?? '' );
			} elseif ( $f['type'] === 'address' ) {
				$val = trim( ( $params[ $name . '_street' ] ?? '' ) . ' ' . ( $params[ $name . '_street2' ] ?? '' ) . ', ' . ( $params[ $name . '_city' ] ?? '' ) . ' ' . ( $params[ $name . '_state' ] ?? '' ) . ' ' . ( $params[ $name . '_zip' ] ?? '' ) );
			}

			$field_map[ $name ] = esc_html( $val );
			$all_fields_text   .= '<div style="margin-bottom: 8px;"><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $val ) . '</div>';
		}
		$all_fields_text .= '</div>';

		// Helper to replace placeholders
		$replace_placeholders = function ( $text ) use ( $field_map, $all_fields_text, $post ) {
			$text = str_replace( '[all_fields]', $all_fields_text, $text );
			$text = str_replace( '[form_title]', esc_html( $post->post_title ), $text );
			foreach ( $field_map as $k => $v ) {
				$text = str_replace( '[' . $k . ']', $v, $text );
			}
			return $text;
		};

		$from_email = get_option( 'admin_email' );

		// Use custom reply-to if set, otherwise fallback to admin email
		$reply_to_email = ! empty( $email_settings['admin_notify_reply_to'] ) ? $email_settings['admin_notify_reply_to'] : $from_email;

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			"Reply-To: $reply_to_email",
		);

		// Send Admin Email
		if ( $admin_notify_enable == '1' ) {
			$to      = $admin_notify_to ?: get_option( 'admin_email' );
			$subject = wp_strip_all_tags( $replace_placeholders( $admin_notify_subject ) );
			$body    = wpautop( $replace_placeholders( $admin_notify_body ) );
			wp_mail( $to, $subject, $body, $headers, $attachments );
		}

		// Send User Email
		if ( $user_confirm_enable == '1' ) {
			$to               = '';
			$user_email_found = false;
			foreach ( $fields as $f ) {
				if ( ( $f['type'] ?? '' ) === 'email' ) {
					$email_name = $f['name'] ?? $f['id'];
					$to         = $params[ $email_name ] ?? '';
					if ( is_email( $to ) ) {
						$user_email_found = true;
						break;
					}
				}
			}

			if ( $user_email_found && is_email( $to ) ) {
				$subject = wp_strip_all_tags( $replace_placeholders( $user_confirm_subject ) );
				$body    = wpautop( $replace_placeholders( $user_confirm_body ) );
				wp_mail( $to, $subject, $body, $headers, $attachments );
			}
		}

		return true;
	}
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function imf_sanitize_field_value( $value, $type ) {
	if ( is_array( $value ) ) {
		return array_map(
			function ( $v ) use ( $type ) {
				return imf_sanitize_field_value( $v, $type );
			},
			$value
		);
	}

	switch ( $type ) {
		case 'email':
			return sanitize_email( $value );
		case 'textarea':
			return sanitize_textarea_field( $value );
		default:
			return sanitize_text_field( $value );
	}
}

/**
 * Compute the HMAC-SHA256 of $body using $secret, returning a header-ready
 * string of the form "sha256=<hex>". Matches the format expected by the
 * standalone app's HmacVerify middleware.
 *
 * Phase 7: the WP plugin signs every forwarded body with this. The
 * signature covers the full body bytes — the standalone reads the raw
 * body and recomputes the signature with the matching shared secret.
 */
/**
 * Resolve a form field's canonical name using the defined priority:
 *
 *   1. Explicit canonical_name (set per-field in form builder)
 *   2. Field name exact match against canonical list
 *   3. Label exact / contains-match fallback (temporary — logged)
 *
 * Returns an array: [ 'canonical' => ?string, 'source' => ?string ]
 *
 * The `source` is one of: 'explicit', 'name', 'label_exact',
 * 'label_contains', or null if no mapping found.
 *
 * @param array $field      Must have 'name', 'label' keys; may have 'canonical_name'.
 * @param array $exactNames List of canonical field names in priority order.
 * @return array{canonical: string|null, source: string|null}
 */
function imf_resolve_canonical( array $field, array $exactNames ): array {
    // 1. Explicit mapping (set per-field in form builder via META_CANONICAL)
    $cn = $field['canonical_name'] ?? '';
    if ( $cn !== '' && in_array( $cn, $exactNames, true ) ) {
        return array( 'canonical' => $cn, 'source' => 'explicit' );
    }

    // 2. Field name exact match
    if ( in_array( $field['name'], $exactNames, true ) ) {
        return array( 'canonical' => $field['name'], 'source' => 'name' );
    }

    // 3. Label inference (temporary fallback — logged downstream)
    $label = strtolower( trim( (string) ( $field['label'] ?? '' ) ) );

    // 3a. Exact label match
    foreach ( $exactNames as $c ) {
        if ( $label === $c ) {
            return array( 'canonical' => $c, 'source' => 'label_exact' );
        }
    }

    // 3b. Contains-match (priority-order matters — "course" beats "course code")
    foreach ( $exactNames as $c ) {
        $needle = str_replace( '_', ' ', $c ); // start_date => "start date"
        if ( $needle !== '' && str_contains( $label, $needle ) ) {
            return array( 'canonical' => $c, 'source' => 'label_contains' );
        }
    }

    return array( 'canonical' => null, 'source' => null );
}

/**
 * Normalize a date string to YYYY-MM-DD format.
 *
 * Accepts: YYYY-MM-DD (passthrough), n/j/Y, m/d/Y, d/m/Y.
 * If none of the formats match, returns the original value unchanged
 * so downstream validation can produce a meaningful error.
 */
function imf_normalize_date( string $val ): string {
    if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $val ) === 1 ) {
        return $val;
    }

    $formats = array( 'n/j/Y', 'm/d/Y', 'd/m/Y' );
    foreach ( $formats as $fmt ) {
        $dt = DateTime::createFromFormat( $fmt, $val );
        if ( $dt !== false ) {
            $errors = DateTime::getLastErrors();
            if ( is_array( $errors ) && $errors['warning_count'] === 0 && $errors['error_count'] === 0 ) {
                return $dt->format( 'Y-m-d' );
            }
        }
    }

    return $val;
}

function imf_hmac_sign( string $body, string $secret ): string {
	return 'sha256=' . hash_hmac( 'sha256', $body, $secret );
}

/**
 * Normalize stored entry fields_data (JSON-decoded) into a canonical list.
 *
 * Handles all historical storage formats:
 *  - Current:  [ ['name'=>'x', 'label'=>'X', 'value'=>'y', 'type'=>'text'], ... ]
 *  - Legacy 1: { "fieldname": "value", ... }   (plain assoc map)
 *  - Legacy 2: [ "value1", "value2", ... ]      (indexed value list — edge case)
 *
 * @param  mixed $raw The json_decode()'d value from fields_data.
 * @return array      Always returns a canonical list-of-objects array.
 */
function imf_normalize_entry_data( $raw ): array {
	if ( empty( $raw ) || ! is_array( $raw ) ) {
		return array();
	}

	// Already in canonical format: first element has 'name' key as a string key (not int).
	$first = reset( $raw );
	if ( is_array( $first ) && array_key_exists( 'name', $first ) ) {
		return $raw;
	}

	// Legacy assoc map: { "fieldname": "value" }
	$normalized = array();
	foreach ( $raw as $key => $val ) {
		if ( is_string( $key ) ) {
			// key is the field name
			if ( is_array( $val ) ) {
				$val = implode( ', ', $val );
			}
			$normalized[] = array(
				'name'  => $key,
				'label' => ucwords( str_replace( array( '-', '_' ), ' ', $key ) ),
				'value' => (string) $val,
				'type'  => 'text',
			);
		} else {
			// Indexed list of scalars — treat index as name
			if ( is_array( $val ) ) {
				$val = implode( ', ', $val );
			}
			$normalized[] = array(
				'name'  => 'field_' . $key,
				'label' => 'Field ' . ( $key + 1 ),
				'value' => (string) $val,
				'type'  => 'text',
			);
		}
	}

	return $normalized;
}

function imf_format_entry_date( $datetime_string ) {
	try {
		$dt = new DateTime( $datetime_string );
		$dt->setTimezone( new DateTimeZone( 'Asia/Manila' ) );
		return $dt->format( 'M j, Y \a\t g:i a' );
	} catch ( Exception $e ) {
		return $datetime_string;
	}
}

function imf_get_appearance_defaults( $post_id ) {
	$appearance = get_post_meta( $post_id, Imedia_Forms::META_APPEARANCE, true );
	$appearance = is_array( $appearance ) ? $appearance : array();
	return wp_parse_args(
		$appearance,
		array(
			'title_color'       => '#1e293b',
			'submit_bg_color'   => '#3b82f6',
			'submit_text_color' => '#ffffff',
			'submit_width'      => 'auto',
			'submit_alignment'  => 'left',
			'enable_recaptcha'  => '0',
			'enable_honeypot'   => '0',
		)
	);
}

function imf_get_email_settings_defaults( $post_id, $form_title = '' ) {
	$email_settings = get_post_meta( $post_id, Imedia_Forms::META_EMAIL_SETTINGS, true );
	$email_settings = is_array( $email_settings ) ? $email_settings : array();
	return wp_parse_args(
		$email_settings,
		array(
			'admin_notify_enable'   => '0',
			'admin_notify_to'       => get_option( 'admin_email' ),
			'admin_notify_reply_to' => '',
			'admin_notify_subject'  => 'New Submission: [form_title]',
			'admin_notify_body'     => 'You have a new submission for [form_title].<br><br>[all_fields]',
			'user_confirm_enable'   => '0',
			'user_confirm_to'       => '',
			'user_confirm_subject'  => 'Thank you for your submission',
			'user_confirm_body'     => 'Hi,<br><br>Thank you for contacting us. Here is a copy of your submission:<br><br>[all_fields]',
		)
	);
}

<?php
if (!defined('ABSPATH')) exit;

class IMF_Rest_API {

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    public function register_rest_routes() {
        register_rest_route('imedia-forms/v1', '/submit', [
            'methods'             => 'POST',
            'callback'            => [$this, 'rest_submit_form'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function rest_submit_form($request) {
        global $wpdb;
        $params = $request->get_params();
        if (empty($params)) {
            $params = $request->get_json_params() ?: [];
        }

        $form_id = intval($params['_imf_form_id'] ?? 0);
        if (!$form_id) return new WP_Error('invalid_form', 'Invalid Form ID', ['status' => 400]);

        $post = get_post($form_id);
        if (!$post || $post->post_type !== Imedia_Forms::CPT) return new WP_Error('not_found', 'Form not found', ['status' => 404]);

        $fields_raw = get_post_meta($form_id, Imedia_Forms::META_FIELDS, true);
        $fields = json_decode($fields_raw, true) ?: [];

        $files = $request->get_file_params();
        if (!empty($files)) {
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }
            foreach ($files as $file_key => $file_data) {
                if (is_array($file_data) && empty($file_data['error'])) {
                    $upload_overrides = ['test_form' => false];
                    $uploaded_file = wp_handle_upload($file_data, $upload_overrides);
                    if ($uploaded_file && !isset($uploaded_file['error'])) {
                        $params[$file_key] = $uploaded_file['url'];
                    }
                }
            }
        }

        $entry_data = [];
        foreach ($fields as $f) {
            if ($f['type'] === 'section' || $f['type'] === 'hidden') continue;
            $name = $f['name'] ?? $f['id'];
            $label = $f['label'];
            $val = $params[$name] ?? '';
            if (is_array($val)) $val = implode(', ', $val);

            if ($f['type'] === 'name') {
                $val = trim(($params[$name . '_first'] ?? '') . ' ' . ($params[$name . '_last'] ?? ''));
            } elseif ($f['type'] === 'date' && ($f['date_input_type'] ?? '') !== 'date_picker') {
                $val = ($params[$name . '_month'] ?? '') . '/' . ($params[$name . '_day'] ?? '') . '/' . ($params[$name . '_year'] ?? '');
            } elseif ($f['type'] === 'address') {
                $val = trim(($params[$name . '_street'] ?? '') . ', ' . ($params[$name . '_street2'] ?? '') . ', ' . ($params[$name . '_city'] ?? '') . ' ' . ($params[$name . '_state'] ?? '') . ' ' . ($params[$name . '_zip'] ?? ''));
            }

            $val = imf_sanitize_field_value($val, $f['type']);

            $entry_data[] = [
                'name'  => $name,
                'label' => $label,
                'value' => $val,
                'type'  => $f['type'],
            ];
        }

        $table = IMF_Database::table_name();
        $wpdb->insert($table, [
            'form_id'     => $form_id,
            'fields_data' => wp_json_encode($entry_data),
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'status'      => 'active',
            'is_read'     => 0,
            'is_starred'  => 0,
            'created_at'  => current_time('mysql'),
        ]);
        $entry_id = $wpdb->insert_id;

        IMF_Email::send_from_request($form_id, $fields, $params, []);

        $api_enabled = get_post_meta($form_id, Imedia_Forms::META_API_ENABLED, true);
        $api_url     = get_post_meta($form_id, Imedia_Forms::META_API_URL, true);

        if ($api_enabled === '1') {
            $clean_data = [];
            foreach ($entry_data as $ed) {
                $clean_data[$ed['name']] = $ed['value'];
            }

            // Phase 7: forward to the standalone app with HMAC signing.
            //
            // Resolve the destination URL. Priority:
            //   1. Per-form META_API_URL override (legacy).
            //   2. Global imf_app_url option.
            //   3. site_url() + '/imedia-registration' (final fallback).
            //
            // The standalone is configured with one shared secret per
            // installation; any per-form override still uses the global
            // secret for signing, since per-form secrets were never a
            // design goal in this phase.
            $per_form_url = is_string($api_url) ? trim($api_url) : '';
            $global_url   = (string) get_option('imf_app_url', '');
            $resolved_url = $per_form_url !== '' ? $per_form_url : (string) $global_url;
            if ($resolved_url === '') {
                $resolved_url = trailingslashit(home_url()) . 'imedia-registration';
            }
            $submit_url = rtrim($resolved_url, '/') . '/api/submit';

            // Build the body. _imf_timestamp is included inside the signed
            // payload so the standalone can enforce a freshness window
            // (replay protection). Use current_time('timestamp') so the
            // timestamp follows WordPress's configured timezone.
            $clean_data['_imf_form_id']  = (int) $form_id;
            $clean_data['_imf_timestamp'] = (int) current_time('timestamp', true);

            // Encode once. We sign the exact bytes we send, then pass the
            // same string to wp_remote_post. Re-serializing would change
            // key order or whitespace and invalidate the signature.
            $body    = wp_json_encode($clean_data);
            $secret  = (string) get_option('imf_shared_secret', '');
            $headers = [
                'Content-Type' => 'application/json; charset=utf-8',
                'Accept'       => 'application/json',
                'User-Agent'   => 'IMediaRegistration/' . Imedia_Forms::VERSION . ' (+' . home_url() . ')',
            ];
            if ($secret !== '') {
                $headers['X-IMF-Signature'] = imf_hmac_sign($body, $secret);
            }

            // Best-effort forward. blocking => false so the visitor's
            // form response is not delayed by the standalone. The
            // local wp_imf_entries row is the safety net.
            $response = wp_remote_post($submit_url, [
                'headers'   => $headers,
                'body'      => $body,
                'timeout'   => 15,
                'blocking'  => false,
                'sslverify' => true,
                'data_format' => 'body',
            ]);

            if (is_wp_error($response)) {
                error_log(sprintf(
                    '[IMedia Registration] Forward to standalone failed: %s (form_id=%d, url=%s)',
                    $response->get_error_message(),
                    (int) $form_id,
                    $submit_url
                ));
            }
            // When blocking=false, wp_remote_post returns a stub
            // WP_HTTP_Requests_Response we cannot reliably inspect. We
            // intentionally do not switch back to blocking=true because a
            // slow / unreachable standalone would stall the visitor.
            // Failures are observable via the error_log entries above
            // and by the absence of a row in the standalone's tables.
        }

        return rest_ensure_response([
            'success'  => true,
            'entry_id' => $entry_id,
        ]);
    }
}

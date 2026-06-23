<?php
/**
 * WordPress stub functions for standalone (no-WP) unit tests.
 *
 * These provide minimal implementations of WordPress functions used
 * by helpers.php so that pure unit tests can run without loading
 * the full WordPress bootstrap.
 *
 * Each function is wrapped in function_exists() so it can be easily
 * replaced by a real WordPress function when running integration tests.
 */

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str): string
    {
        if (is_array($str)) {
            return '';
        }
        $str = (string) $str;
        // Strip HTML tags and dangerous characters (mimicking WP behavior)
        $str = strip_tags($str);
        // Remove anything that isn't printable ASCII, spaces, or basic punctuation
        $str = preg_replace('/[^\x20-\x7E\s]/', '', $str);
        return trim($str);
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email): string
    {
        $email = trim((string) $email);
        if (!preg_match('/^[^@\s]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            return '';
        }
        return strtolower($email);
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str): string
    {
        if (is_array($str)) {
            return '';
        }
        $str = (string) $str;
        $str = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $str);
        return trim($str);
    }
}

if (!function_exists('sanitize_hex_color')) {
    function sanitize_hex_color($color)
    {
        $color = trim((string) $color);
        if ('' === $color) {
            return '';
        }
        if (preg_match('|^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$|', $color)) {
            return $color;
        }
        return '';
    }
}

if (!function_exists('sanitize_url')) {
    function sanitize_url($url, $protocols = null): string
    {
        return esc_url_raw($url, $protocols);
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url, $protocols = null): string
    {
        $url = trim((string) $url);
        if ('' === $url) {
            return '';
        }
        if (!preg_match('#^https?://#i', $url)) {
            return '';
        }
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }
}

if (!function_exists('esc_url')) {
    function esc_url($url, $protocols = null): string
    {
        return esc_url_raw($url, $protocols);
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text): string
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key): string
    {
        $key = (string) $key;
        return preg_replace('/[^a-zA-Z0-9_\-]/', '', $key);
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        if (is_array($value)) {
            return array_map('wp_unslash', $value);
        }
        return stripslashes((string) $value);
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = []): array
    {
        if (is_object($args)) {
            $parsed = get_object_vars($args);
        } elseif (is_array($args)) {
            $parsed = $args;
        } else {
            $parsed = [];
        }
        return array_merge($defaults, $parsed);
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($string, $remove_breaks = false): string
    {
        $string = preg_replace('@<[^>]*>@', '', (string) $string);
        if (!$remove_breaks) {
            $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
        }
        return trim($string);
    }
}

if (!function_exists('wpautop')) {
    function wpautop($pee, $br = true): string
    {
        return '<p>' . str_replace(["\r\n", "\r", "\n"], "</p>\n<p>", trim((string) $pee)) . '</p>';
    }
}

if (!function_exists('is_email')) {
    function is_email($email): bool
    {
        return filter_var((string) $email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('wp_validate_url')) {
    function wp_validate_url($url): string|false
    {
        if (!is_string($url) || $url === '') {
            return false;
        }
        return filter_var($url, FILTER_VALIDATE_URL) ? $url : false;
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = false)
    {
        if ($type === 'mysql') {
            return $gmt ? gmdate('Y-m-d H:i:s') : date('Y-m-d H:i:s');
        }
        if ($type === 'timestamp') {
            return $gmt ? time() : time();
        }
        return $gmt ? gmdate('Y-m-d H:i:s') : date('Y-m-d H:i:s');
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false)
    {
        static $options = [];
        return array_key_exists($option, $options) ? $options[$option] : $default;
    }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false)
    {
        return $single ? '' : [];
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta($post_id, $meta_key, $meta_value): bool
    {
        return true;
    }
}

if (!function_exists('get_post')) {
    function get_post($post = null, $output = OBJECT, $filter = 'raw')
    {
        return null;
    }
}

if (!function_exists('esc_sql')) {
    function esc_sql($data)
    {
        return $data;
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data, $options = 0, $depth = 512): string|false
    {
        return json_encode($data, $options, $depth);
    }
}

if (!function_exists('trailingslashit')) {
    function trailingslashit($string): string
    {
        return rtrim((string) $string, '/\\') . '/';
    }
}

if (!function_exists('home_url')) {
    function home_url($path = '', $scheme = null): string
    {
        return 'http://aguefortacademy.test' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('rest_url')) {
    function rest_url($path = ''): string
    {
        return 'http://aguefortacademy.test/wp-json/' . ltrim($path, '/');
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '', $scheme = 'admin'): string
    {
        return 'http://aguefortacademy.test/wp-admin/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = []): array|\WP_Error
    {
        return ['response' => ['code' => 200], 'body' => '{"success":true}'];
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing): bool
    {
        return $thing instanceof \WP_Error;
    }
}

if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = '', $attachments = []): bool
    {
        return true;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($hook_name, ...$args)
    {
        return $args[0] ?? null;
    }
}

if (!function_exists('do_action')) {
    function do_action($hook_name, ...$args): void
    {
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1): void
    {
    }
}

if (!function_exists('add_action')) {
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1): void
    {
    }
}

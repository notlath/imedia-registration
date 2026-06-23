<?php
declare(strict_types=1);

namespace IMF\Tests\Support;

use WP_UnitTest_Factory_For_Post;
use WP_UnitTest_Factory_For_User;

class FormFactory
{
    public static function createForm(array $fields = [], array $meta = []): int
    {
        $meta = array_merge([
            '_imf_form_status'  => 'active',
            '_imf_form_fields'  => wp_json_encode($fields),
            '_imf_api_enabled'  => '0',
            '_imf_api_url'      => '',
        ], $meta);

        $post_data = [
            'post_title'  => 'Test Form',
            'post_type'   => 'imedia_form',
            'post_status' => 'publish',
        ];

        $post_id = wp_insert_post($post_data);
        if (is_wp_error($post_id)) {
            throw new \RuntimeException('Failed to create form: ' . $post_id->get_error_message());
        }

        foreach ($meta as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }

        return $post_id;
    }

    public static function createDefaultField(string $type, string $name = '', array $overrides = []): array
    {
        $name = $name ?: 'field_' . $type;
        $defaults = [
            'name'      => $name,
            'id'        => $name,
            'type'      => $type,
            'label'     => ucfirst($type) . ' Field',
            'required'  => false,
            'width'     => '100',
        ];

        switch ($type) {
            case 'name':
                break;
            case 'date':
                $defaults['date_input_type'] = 'date_picker';
                break;
            case 'address':
                break;
            case 'select':
            case 'multiselect':
            case 'radio':
            case 'checkbox':
            case 'multiple_choice':
                $defaults['options'] = "Option A\nOption B\nOption C";
                break;
            case 'email':
                $defaults['confirm_email'] = false;
                break;
            case 'file':
                $defaults['accepted_formats'] = '.pdf,.doc';
                break;
        }

        return array_merge($defaults, $overrides);
    }

    public static function createEntry(int $form_id, array $entry_data = []): int
    {
        global $wpdb;
        $table = \IMF_Database::table_name();

        $defaults = [
            'form_id'     => $form_id,
            'fields_data' => wp_json_encode($entry_data),
            'ip_address'  => '127.0.0.1',
            'user_agent'  => 'PHPUnit/10.5',
            'status'      => 'active',
            'is_read'     => 0,
            'is_starred'  => 0,
            'created_at'  => current_time('mysql'),
        ];

        $data = array_merge($defaults, $entry_data);
        $wpdb->insert($table, $data);

        if (!$wpdb->insert_id) {
            throw new \RuntimeException('Failed to create entry: ' . $wpdb->last_error);
        }

        return (int) $wpdb->insert_id;
    }

    public static function createUser(string $role = 'administrator'): int
    {
        $username = 'test_user_' . wp_rand(1000, 9999);
        $user_id = wp_insert_user([
            'user_login' => $username,
            'user_pass'  => 'password',
            'user_email' => $username . '@test.test',
            'role'       => $role,
        ]);

        if (is_wp_error($user_id)) {
            throw new \RuntimeException('Failed to create user: ' . $user_id->get_error_message());
        }

        return $user_id;
    }

    public static function deleteAll(int $form_id = 0): void
    {
        global $wpdb;

        if ($form_id > 0) {
            $wpdb->delete(\IMF_Database::table_name(), ['form_id' => $form_id], ['%d']);
        } else {
            $wpdb->query('TRUNCATE TABLE ' . \IMF_Database::table_name());
        }
    }

    public static function makeRestRequest(string $method, string $route, array $params = []): \WP_REST_Request
    {
        $request = new \WP_REST_Request($method, $route);
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(wp_json_encode($params));
        return $request;
    }
}

<?php
declare(strict_types=1);

namespace IMF\Tests\Integration;

use IMF\Tests\Support\FormFactory;

class RestSubmitFormTest extends \WP_UnitTestCase
{
    private int $formId;
    private array $capturedEmails = [];
    private array $capturedHttpRequests = [];

    public function setUp(): void
    {
        parent::setUp();

        $fields = [
            FormFactory::createDefaultField('text', 'full_name', ['required' => true, 'label' => 'Full Name']),
            FormFactory::createDefaultField('email', 'email_addr', ['required' => true, 'label' => 'Email Address']),
            FormFactory::createDefaultField('textarea', 'message', ['label' => 'Message']),
            FormFactory::createDefaultField('name', 'applicant_name', ['label' => 'Applicant Name']),
            FormFactory::createDefaultField('date', 'birth_date', ['label' => 'Birth Date', 'date_input_type' => 'date_picker']),
            FormFactory::createDefaultField('address', 'home_address', ['label' => 'Home Address']),
            FormFactory::createDefaultField('select', 'course', ['label' => 'Course', 'options' => "Math\nScience\nArts"]),
            FormFactory::createDefaultField('multiselect', 'interests', ['label' => 'Interests', 'options' => "Reading\nSports\nMusic"]),
            FormFactory::createDefaultField('checkbox', 'agree', ['label' => 'Agree to terms']),
            FormFactory::createDefaultField('radio', 'gender', ['label' => 'Gender', 'options' => "Male\nFemale"]),
            FormFactory::createDefaultField('phone', 'mobile', ['label' => 'Mobile']),
            FormFactory::createDefaultField('number', 'age_range', ['label' => 'Age']),
            FormFactory::createDefaultField('section', '', ['label' => 'Section Divider', 'default_value' => 'Section info']),
            FormFactory::createDefaultField('hidden', 'tracking_id', ['default_value' => 'ABC123']),
        ];

        $this->formId = FormFactory::createForm($fields, [
            '_imf_api_enabled' => '0',
        ]);

        $this->capturedEmails = [];
        $this->capturedHttpRequests = [];

        add_filter('pre_wp_mail', function ($args) {
            $this->capturedEmails[] = $args;
            return true;
        });

        add_filter('pre_http_request', function ($response, $parsed_args, $url) {
            $this->capturedHttpRequests[] = ['url' => $url, 'args' => $parsed_args];
            return ['response' => ['code' => 200], 'body' => '{"success":true}', 'headers' => []];
        }, 10, 3);

        do_action('rest_api_init');
    }

    public function tearDown(): void
    {
        FormFactory::deleteAll($this->formId);
        wp_delete_post($this->formId, true);
        remove_all_filters('pre_wp_mail');
        remove_all_filters('pre_http_request');
        parent::tearDown();
    }

    private function dispatchSubmit(array $params): \WP_REST_Response
    {
        $request = FormFactory::makeRestRequest('POST', '/imedia-forms/v1/submit', $params);
        return rest_get_server()->dispatch($request);
    }

    // ----- 2.1: Form ID validation -----

    public function test_missing_form_id_returns_400(): void
    {
        $response = $this->dispatchSubmit(['full_name' => 'John']);
        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertSame('invalid_form', $data['code']);
        $this->assertSame(400, $data['data']['status']);
    }

    public function test_zero_form_id_returns_400(): void
    {
        $response = $this->dispatchSubmit(['_imf_form_id' => 0]);
        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertSame('invalid_form', $data['code']);
    }

    public function test_string_form_id_returns_400(): void
    {
        $response = $this->dispatchSubmit(['_imf_form_id' => 'abc']);
        $this->assertSame(400, $response->get_status());
        $data = $response->get_data();
        $this->assertSame('invalid_form', $data['code']);
    }

    public function test_nonexistent_form_returns_404(): void
    {
        $response = $this->dispatchSubmit(['_imf_form_id' => 99999]);
        $this->assertSame(404, $response->get_status());
        $data = $response->get_data();
        $this->assertSame('not_found', $data['code']);
        $this->assertSame(404, $data['data']['status']);
    }

    public function test_wrong_post_type_returns_404(): void
    {
        $page_id = self::factory()->post->create(['post_type' => 'page']);
        $response = $this->dispatchSubmit(['_imf_form_id' => $page_id]);
        $this->assertSame(404, $response->get_status());
        $data = $response->get_data();
        $this->assertSame('not_found', $data['code']);
        wp_delete_post($page_id, true);
    }

    // ----- 2.2: Fields meta parsing -----

    public function test_valid_form_proceeds(): void
    {
        $response = $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John Doe',
            'email_addr' => 'john@test.com',
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
        $this->assertIsInt($data['entry_id']);
    }

    // ----- 2.3: Field type handling -----

    public function test_text_field_stored_correctly(): void
    {
        $response = $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John Doe',
            'email_addr' => 'john@test.com',
        ]);
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    public function test_name_field_combines_parts(): void
    {
        $response = $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
            'applicant_name_first' => 'Jane',
            'applicant_name_last' => 'Doe',
        ]);
        $this->assertSame(200, $response->get_status());
    }

    public function test_name_field_partial(): void
    {
        $response = $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
            'applicant_name_first' => 'Jane',
        ]);
        $this->assertSame(200, $response->get_status());
    }

    public function test_date_picker_field(): void
    {
        $response = $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
            'birth_date' => '2024-06-15',
        ]);
        $this->assertSame(200, $response->get_status());
    }

    public function test_address_field_assembled(): void
    {
        $response = $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
            'home_address_street' => '123 Main St',
            'home_address_city' => 'Springfield',
        ]);
        $this->assertSame(200, $response->get_status());
    }

    public function test_multiselect_field_arrays_to_string(): void
    {
        $response = $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
            'interests' => ['Reading', 'Sports'],
        ]);
        $this->assertSame(200, $response->get_status());
    }

    public function test_checkbox_array_to_string(): void
    {
        $response = $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
            'agree' => ['option1', 'option2'],
        ]);
        $this->assertSame(200, $response->get_status());
    }

    public function test_phone_field(): void
    {
        $response = $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
            'mobile' => '+1-555-1234',
        ]);
        $this->assertSame(200, $response->get_status());
    }

    public function test_hidden_field_skipped(): void
    {
        $response = $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
        ]);

        global $wpdb;
        $entry_id = $response->get_data()['entry_id'];
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT fields_data FROM {$wpdb->prefix}imf_entries WHERE id = %d", $entry_id
        ));
        $fields_data = json_decode($entry->fields_data, true);
        $tracking_id = array_filter($fields_data, fn($f) => $f['name'] === 'tracking_id');
        $this->assertEmpty($tracking_id);
    }

    // ----- 2.5: XSS prevention -----

    public function test_xss_in_text_field_is_stripped(): void
    {
        $response = $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => '<script>alert(1)</script>John',
            'email_addr' => 'j@t.com',
        ]);
        $this->assertSame(200, $response->get_status());
    }

    // ----- 2.6: Entry insertion defaults -----

    public function test_entry_inserted_with_correct_defaults(): void
    {
        $response = $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
        ]);

        global $wpdb;
        $entry_id = $response->get_data()['entry_id'];
        $entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}imf_entries WHERE id = %d", $entry_id
        ));

        $this->assertSame('active', $entry->status);
        $this->assertSame('0', $entry->is_read);
        $this->assertSame('0', $entry->is_starred);
        $this->assertSame($this->formId, (int) $entry->form_id);
    }

    // ----- 2.7: Email dispatch -----

    public function test_email_dispatched_on_submit(): void
    {
        update_post_meta($this->formId, '_imf_email_settings', [
            'admin_notify_enable'  => '1',
            'admin_notify_to'      => 'admin@test.com',
            'admin_notify_subject' => 'New Submission: [form_title]',
            'admin_notify_body'    => '[all_fields]',
        ]);

        $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
        ]);

        $this->assertNotEmpty($this->capturedEmails);
    }

    // ----- 2.8: API forwarding -----

    public function test_no_forwarding_when_api_disabled(): void
    {
        $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
        ]);

        $forwarded = array_filter($this->capturedHttpRequests, fn($r) =>
            str_contains($r['url'], '/api/submit')
        );
        $this->assertEmpty($forwarded);
    }

    public function test_forward_uses_fallback_url_when_no_meta(): void
    {
        update_post_meta($this->formId, '_imf_api_enabled', '1');

        $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
        ]);

        $forwarded = current(array_filter($this->capturedHttpRequests, fn($r) =>
            str_contains($r['url'], '/api/submit')
        ));
        $this->assertNotFalse($forwarded);
        $this->assertStringContainsString('/imedia-registration/api/submit', $forwarded['url']);
    }

    public function test_forward_uses_per_form_url_override(): void
    {
        update_post_meta($this->formId, '_imf_api_enabled', '1');
        update_post_meta($this->formId, '_imf_api_endpoint', 'https://custom.example.com');

        $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
        ]);

        $forwarded = current(array_filter($this->capturedHttpRequests, fn($r) =>
            str_contains($r['url'], '/api/submit')
        ));
        $this->assertNotFalse($forwarded);
        $this->assertStringContainsString('custom.example.com', $forwarded['url']);
    }

    public function test_forward_includes_form_id_and_timestamp(): void
    {
        update_post_meta($this->formId, '_imf_api_enabled', '1');

        $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
        ]);

        $forwarded = current(array_filter($this->capturedHttpRequests, fn($r) =>
            str_contains($r['url'], '/api/submit')
        ));

        $body = json_decode($forwarded['args']['body'], true);
        $this->assertArrayHasKey('_imf_form_id', $body);
        $this->assertSame($this->formId, (int) $body['_imf_form_id']);
        $this->assertArrayHasKey('_imf_timestamp', $body);
        $this->assertIsInt($body['_imf_timestamp']);
        $this->assertGreaterThan(0, $body['_imf_timestamp']);
    }

    public function test_forward_sets_correct_headers(): void
    {
        update_post_meta($this->formId, '_imf_api_enabled', '1');

        $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
        ]);

        $forwarded = current(array_filter($this->capturedHttpRequests, fn($r) =>
            str_contains($r['url'], '/api/submit')
        ));

        $headers = $forwarded['args']['headers'];
        $this->assertSame('application/json; charset=utf-8', $headers['Content-Type']);
        $this->assertSame('application/json', $headers['Accept']);
        $this->assertStringContainsString('IMediaRegistration/3.0.0', $headers['User-Agent']);
    }

    public function test_forward_skips_signature_when_secret_empty(): void
    {
        update_post_meta($this->formId, '_imf_api_enabled', '1');
        update_option('imf_shared_secret', '');

        $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
        ]);

        $forwarded = current(array_filter($this->capturedHttpRequests, fn($r) =>
            str_contains($r['url'], '/api/submit')
        ));

        $this->assertArrayNotHasKey('X-IMF-Signature', $forwarded['args']['headers']);
    }

    public function test_forward_includes_hmac_signature_when_secret_set(): void
    {
        update_post_meta($this->formId, '_imf_api_enabled', '1');
        update_option('imf_shared_secret', 'test-secret-123');

        $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
        ]);

        $forwarded = current(array_filter($this->capturedHttpRequests, fn($r) =>
            str_contains($r['url'], '/api/submit')
        ));

        $this->assertArrayHasKey('X-IMF-Signature', $forwarded['args']['headers']);
        $this->assertStringStartsWith('sha256=', $forwarded['args']['headers']['X-IMF-Signature']);
    }

    public function test_forward_uses_blocking_false(): void
    {
        update_post_meta($this->formId, '_imf_api_enabled', '1');

        $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
        ]);

        $forwarded = current(array_filter($this->capturedHttpRequests, fn($r) =>
            str_contains($r['url'], '/api/submit')
        ));

        $this->assertFalse($forwarded['args']['blocking']);
        $this->assertSame(15, $forwarded['args']['timeout']);
    }

    public function test_forward_body_matches_signed_bytes(): void
    {
        update_post_meta($this->formId, '_imf_api_enabled', '1');
        update_option('imf_shared_secret', 'test-secret-123');

        $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
        ]);

        $forwarded = current(array_filter($this->capturedHttpRequests, fn($r) =>
            str_contains($r['url'], '/api/submit')
        ));

        $body = $forwarded['args']['body'];
        $signature = $forwarded['args']['headers']['X-IMF-Signature'];
        $expected = 'sha256=' . hash_hmac('sha256', $body, 'test-secret-123');
        $this->assertSame($expected, $signature);
    }

    public function test_forward_wp_remote_post_failure_logged_but_success_returned(): void
    {
        remove_all_filters('pre_http_request');
        add_filter('pre_http_request', function () {
            return new \WP_Error('http_failure', 'Connection timed out');
        }, 10, 3);

        update_post_meta($this->formId, '_imf_api_enabled', '1');

        $response = $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
        ]);

        $this->assertSame(200, $response->get_status());
        $data = $response->get_data();
        $this->assertTrue($data['success']);
    }

    // ----- 2.9: Response shapes -----

    public function test_success_response_shape(): void
    {
        $response = $this->dispatchSubmit([
            '_imf_form_id' => $this->formId,
            'full_name' => 'John',
            'email_addr' => 'j@t.com',
        ]);

        $data = $response->get_data();
        $this->assertArrayHasKey('success', $data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('entry_id', $data);
    }
}

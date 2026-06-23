<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Controllers;

use App\Core\{Request, Response};
use App\Controllers\SubmitController;
use App\Models\Registration;
use App\Models\StatusHistory;
use IMF\Tests\Support\ControllerTestCase;

/**
 * Priority 1 — SubmitController integration tests.
 *
 * Tests SubmitController::handle() directly (bypassing HmacVerify
 * middleware, which is tested separately in Tier 1).  Covers request
 * validation, database persistence, form-route routing, and error
 * handling.
 *
 * @covers \App\Controllers\SubmitController
 */
class SubmitControllerTest extends ControllerTestCase
{
    // -----------------------------------------------------------------
    // Happy path
    // -----------------------------------------------------------------

    public function test_happy_path_returns_201_and_persists(): void
    {
        $ctrl = new SubmitController();
        $req = new Request('POST', '/api/submit', [], [
            'form_id' => 9999,
            'fields' => [
                'name'       => 'Submit Test',
                'email'      => 'submit@test.com',
                'course'     => 'Integration Testing',
                'start_date' => '2026-07-01',
                'end_date'   => '2026-08-01',
            ],
        ], [], []);
        $res = new Response();

        $result = $ctrl->handle($req, $res);

        $this->assertSame(201, $result->getStatus());
        $body = json_decode($result->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertIsInt($body['id']);

        // Persistence
        $row = Registration::find($body['id']);
        $this->assertIsArray($row);
        $this->assertSame('Submit Test', $row['name']);
        $this->assertSame('submit@test.com', $row['email']);

        // Status history
        $history = StatusHistory::forEntity('registration', $body['id']);
        $this->assertCount(1, $history);
        $this->assertSame('pending', $history[0]['new_value']);
    }

    // -----------------------------------------------------------------
    // Request validation
    // -----------------------------------------------------------------

    public function test_missing_form_id_returns_400(): void
    {
        $ctrl = new SubmitController();
        $req = new Request('POST', '/api/submit', [], [
            'fields' => ['name' => 'T'],
        ], [], []);
        $res = new Response();

        $result = $ctrl->handle($req, $res);

        $this->assertSame(400, $result->getStatus());
        $body = json_decode($result->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertSame('invalid_form_id', $body['error']);
    }

    public function test_zero_form_id_returns_400(): void
    {
        $ctrl = new SubmitController();
        $req = new Request('POST', '/api/submit', [], [
            'form_id' => 0,
            'fields' => ['name' => 'T'],
        ], [], []);
        $res = new Response();

        $result = $ctrl->handle($req, $res);

        $this->assertSame(400, $result->getStatus());
        $body = json_decode($result->getBody(), true);
        $this->assertSame('invalid_form_id', $body['error']);
    }

    public function test_missing_fields_returns_400(): void
    {
        $ctrl = new SubmitController();
        $req = new Request('POST', '/api/submit', [], [
            'form_id' => 9999,
        ], [], []);
        $res = new Response();

        $result = $ctrl->handle($req, $res);

        $this->assertSame(400, $result->getStatus());
        $body = json_decode($result->getBody(), true);
        $this->assertSame('invalid_fields', $body['error']);
    }

    public function test_unknown_form_id_returns_404(): void
    {
        $ctrl = new SubmitController();
        $req = new Request('POST', '/api/submit', [], [
            'form_id' => 8888,
            'fields' => ['name' => 'T'],
        ], [], []);
        $res = new Response();

        $result = $ctrl->handle($req, $res);

        $this->assertSame(404, $result->getStatus());
        $body = json_decode($result->getBody(), true);
        $this->assertSame('no_route', $body['error']);
    }

    public function test_empty_fields_returns_422(): void
    {
        $ctrl = new SubmitController();
        $req = new Request('POST', '/api/submit', [], [
            'form_id' => 9999,
            'fields' => [],
        ], [], []);
        $res = new Response();

        $result = $ctrl->handle($req, $res);

        $this->assertSame(422, $result->getStatus());
        $body = json_decode($result->getBody(), true);
        $this->assertStringContainsString('Name is required', $body['message'] ?? '');
        $this->assertStringContainsString('A valid email', $body['message'] ?? '');
        $this->assertStringContainsString('Course is required', $body['message'] ?? '');
    }

    public function test_invalid_email_returns_422(): void
    {
        $ctrl = new SubmitController();
        $req = new Request('POST', '/api/submit', [], [
            'form_id' => 9999,
            'fields' => [
                'name'  => 'Test User',
                'email' => 'not-an-email',
                'course' => 'Test',
                'start_date' => '2026-07-01',
                'end_date'   => '2026-08-01',
            ],
        ], [], []);
        $res = new Response();

        $result = $ctrl->handle($req, $res);

        $this->assertSame(422, $result->getStatus());
        $body = json_decode($result->getBody(), true);
        $this->assertStringContainsString('valid email', $body['message'] ?? '');
    }

    public function test_missing_dates_returns_422(): void
    {
        $ctrl = new SubmitController();
        $req = new Request('POST', '/api/submit', [], [
            'form_id' => 9999,
            'fields' => ['name' => 'No Dates', 'email' => 'nd@test.com', 'course' => 'Test'],
        ], [], []);
        $res = new Response();

        $result = $ctrl->handle($req, $res);

        $this->assertSame(422, $result->getStatus());
        $body = json_decode($result->getBody(), true);
        $this->assertStringContainsString('Start date is required', $body['message'] ?? '');
        $this->assertStringContainsString('End date is required', $body['message'] ?? '');
    }

    // -----------------------------------------------------------------
    // Replay / duplicate — documents current behaviour
    // -----------------------------------------------------------------

    /**
     * @todo Implement replay protection using nonce/timestamp tracking.
     *
     * Current behavior:
     * Identical signed requests submitted within the accepted freshness
     * window are treated as independent requests.  No nonce or
     * idempotency key is checked.
     */
    public function test_replay_creates_distinct_rows(): void
    {
        // Replay protection is via timestamp freshness (300s) only.
        // Without a nonce table, the same payload submitted twice
        // creates two distinct rows.
        $ctrl = new SubmitController();
        $body = [
            'form_id' => 9999,
            'fields' => [
                'name'       => 'Replay Test',
                'email'      => 'replay@test.com',
                'course'     => 'Test',
                'start_date' => '2026-07-01',
                'end_date'   => '2026-08-01',
            ],
        ];

        $req1 = new Request('POST', '/api/submit', [], $body, [], []);
        $res1 = $ctrl->handle($req1, new Response());
        $this->assertSame(201, $res1->getStatus());

        $req2 = new Request('POST', '/api/submit', [], $body, [], []);
        $res2 = $ctrl->handle($req2, new Response());
        $this->assertSame(201, $res2->getStatus());

        $id1 = json_decode($res1->getBody(), true)['id'];
        $id2 = json_decode($res2->getBody(), true)['id'];
        $this->assertNotSame($id1, $id2, 'Each submission creates a distinct row');
    }

    // -----------------------------------------------------------------
    // Field splitting into typed + dynamic
    // -----------------------------------------------------------------

    public function test_extra_fields_go_to_dynamic_data(): void
    {
        $ctrl = new SubmitController();
        $req = new Request('POST', '/api/submit', [], [
            'form_id' => 9999,
            'fields' => [
                'name'        => 'Dynamic',
                'email'       => 'dyn@test.com',
                'course'      => 'Test',
                'start_date'  => '2026-07-01',
                'end_date'    => '2026-08-01',
                'custom_note' => 'Extra field value',
            ],
        ], [], []);
        $res = new Response();

        $result = $ctrl->handle($req, $res);
        $this->assertSame(201, $result->getStatus());

        $id = json_decode($result->getBody(), true)['id'];
        $row = Registration::find($id);
        $this->assertSame('Extra field value', $row['dynamic_data']['custom_note']);
    }
}

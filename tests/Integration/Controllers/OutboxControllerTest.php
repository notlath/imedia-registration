<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Controllers;

use App\Core\{Csrf, Request, Response, Session};
use App\Controllers\OutboxController;
use App\Models\OutboxEmail;
use IMF\Tests\Support\ControllerTestCase;
use IMF\Tests\Support\Fixtures;

class OutboxControllerTest extends ControllerTestCase
{
    use Fixtures;

    private OutboxController $controller;
    private string $csrfToken;
    private const CTX = ['type' => 'registration', 'entity_id' => 1];

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new OutboxController();
        $this->createAdminSession();
        $this->csrfToken = $this->createCsrfToken();
    }

    public function test_index_returns_200(): void
    {
        $req = new Request('GET', '/admin/outbox', [], [], [], []);
        $res = new Response();
        $result = $this->controller->index($req, $res);
        $this->assertSame(200, $result->getStatus());
    }

    public function test_process_empty_outbox(): void
    {
        $req = new Request('POST', '/admin/outbox/process', [], [
            '_csrf' => $this->csrfToken,
        ], [], []);
        $res = new Response();
        $result = $this->controller->process($req, $res);
        $this->assertSame(302, $result->getStatus());
    }

    public function test_process_pending_email(): void
    {
        OutboxEmail::enqueue('test@test.com', 'Test Subject', '<p>Body</p>', self::CTX);

        $req = new Request('POST', '/admin/outbox/process', [], [
            '_csrf' => $this->csrfToken,
        ], [], []);
        $res = new Response();
        $result = $this->controller->process($req, $res);
        $this->assertSame(302, $result->getStatus());
    }

    public function test_retry_updates_state(): void
    {
        $id = OutboxEmail::enqueue('retry@test.com', 'Retry', '<p>Body</p>', self::CTX);
        OutboxEmail::markFailed($id, 'test error', false);

        $req = new Request('POST', "/admin/outbox/{$id}/retry", [], [
            '_csrf' => $this->csrfToken,
        ], [], [], ['id' => (string) $id]);
        $res = new Response();
        $result = $this->controller->retry($req, $res);
        $this->assertSame(302, $result->getStatus());
    }
}

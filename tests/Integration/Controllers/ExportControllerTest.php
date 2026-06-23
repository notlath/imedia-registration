<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Controllers;

use App\Core\{Request, Response, Session};
use App\Controllers\ExportController;
use IMF\Tests\Support\ControllerTestCase;
use IMF\Tests\Support\Fixtures;

class ExportControllerTest extends ControllerTestCase
{
    use Fixtures;

    private ExportController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new ExportController();
        $this->createAdminSession();
    }

    public function test_export_registrations_csv(): void
    {
        $req = new Request('GET', '/admin/export/registrations.csv', [], [], [], []);
        $res = new Response();
        ob_start();
        $result = $this->controller->stream($req, $res);
        $output = ob_get_clean();

        $this->assertSame(200, $result->getStatus());
        $this->assertStringContainsString('id,name,', $output ?? '');
    }

    public function test_export_contacts_csv(): void
    {
        $req = new Request('GET', '/admin/export/contacts.csv', [], [], [], []);
        $res = new Response();
        ob_start();
        $result = $this->controller->stream($req, $res);
        $output = ob_get_clean();

        $this->assertSame(200, $result->getStatus());
        $this->assertStringContainsString('id,name,', $output ?? '');
    }

    public function test_unknown_export_returns_404(): void
    {
        $req = new Request('GET', '/admin/export/unknown.csv', [], [], [], []);
        $res = new Response();
        $result = $this->controller->stream($req, $res);
        $this->assertSame(404, $result->getStatus());
    }
}

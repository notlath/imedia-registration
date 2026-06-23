<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Controllers;

use App\Core\{Csrf, Request, Response, Session};
use App\Controllers\SettingsController;
use App\Models\Setting;
use IMF\Tests\Support\ControllerTestCase;
use IMF\Tests\Support\Fixtures;

class SettingsControllerTest extends ControllerTestCase
{
    use Fixtures;

    private SettingsController $controller;
    private string $csrfToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new SettingsController();
        $this->createAdminSession();
        $this->csrfToken = $this->createCsrfToken();
    }

    public function test_show_form_returns_200(): void
    {
        $req = new Request('GET', '/admin/settings', [], [], [], []);
        $res = new Response();
        $result = $this->controller->show($req, $res);
        $this->assertSame(200, $result->getStatus());
    }

    public function test_save_updates_settings(): void
    {
        $req = new Request('POST', '/admin/settings', [], [
            '_csrf' => $this->csrfToken,
            'site_name' => 'Updated Site',
            'alert_threshold' => '9',
        ], [], []);
        $res = new Response();
        $result = $this->controller->save($req, $res);
        $this->assertSame(302, $result->getStatus());

        Setting::refresh();
        $this->assertSame('Updated Site', Setting::get('site_name'));
    }

    public function test_save_invalid_threshold_fails(): void
    {
        $req = new Request('POST', '/admin/settings', [], [
            '_csrf' => $this->csrfToken,
            'site_name' => 'Test',
            'alert_threshold' => '0',
        ], [], []);
        $res = new Response();
        $result = $this->controller->save($req, $res);
        $this->assertSame(302, $result->getStatus());

        Session::start();
        $errors = Session::pullFlash('errors');
        $this->assertIsArray($errors);
        $this->assertArrayHasKey('alert_threshold', $errors);
    }
}

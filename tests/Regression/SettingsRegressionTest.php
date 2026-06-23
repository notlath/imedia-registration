<?php
declare(strict_types=1);

namespace IMF\Tests\Regression;

use App\Core\{Csrf, Request, Response, Router, Session};
use App\Models\Setting;
use IMF\Tests\Support\DatabaseTestCase;

/**
 * Regression: Settings save/load.
 */
class SettingsRegressionTest extends DatabaseTestCase
{
    public function test_settings_page_loads(): void
    {
        Session::start();
        Session::put('_admin', ['id' => 1, 'name' => 'A', 'email' => 'a@a.com', 'role' => 'super']);

        $router = new Router();
        (require dirname(__DIR__, 2) . '/routes.php')($router);

        $res = $router->dispatch(new Request('GET', '/admin/settings', [], [], [], []), new Response());
        $this->assertSame(200, $res->getStatus());
    }

    public function test_setting_persistence(): void
    {
        $original = Setting::get('site_name');
        Setting::put('site_name', 'Regression Test');
        $this->assertSame('Regression Test', Setting::get('site_name'));
        Setting::put('site_name', $original);
    }
}

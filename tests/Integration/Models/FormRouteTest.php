<?php
declare(strict_types=1);

namespace IMF\Tests\Integration\Models;

use App\Models\FormRoute;
use IMF\Tests\Support\DatabaseTestCase;

class FormRouteTest extends DatabaseTestCase
{
    public function test_find_existing_returns_route(): void
    {
        $route = FormRoute::find(9999);
        $this->assertIsArray($route);
        $this->assertSame('registration', $route['target_type']);
    }

    public function test_find_nonexistent_returns_null(): void
    {
        $this->assertNull(FormRoute::find(999999));
    }

    public function test_upsert_creates_route(): void
    {
        $formId = 5000 + time() % 1000;
        FormRoute::upsert($formId, 'contact');
        $route = FormRoute::find($formId);
        $this->assertIsArray($route);
        $this->assertSame('contact', $route['target_type']);
    }

    public function test_delete_removes_route(): void
    {
        $formId = 6000 + time() % 1000;
        FormRoute::upsert($formId, 'registration');
        FormRoute::delete($formId);
        $this->assertNull(FormRoute::find($formId));
    }
}

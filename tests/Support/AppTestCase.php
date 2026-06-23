<?php
declare(strict_types=1);

namespace IMF\Tests\Support;

use App\Core\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Base class for Tier 1 (no-DB) standalone app tests.
 *
 * Bootstraps the standalone application once per test case file
 * (via setUpBeforeClass) so that autoloading, config, and error
 * reporting are configured before any test runs.
 */
abstract class AppTestCase extends TestCase
{
    protected static bool $booted = false;

    public static function setUpBeforeClass(): void
    {
        if (!self::$booted) {
            require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
            require_once dirname(__DIR__, 2) . '/app/Core/Bootstrap.php';
            Bootstrap::init();
            self::$booted = true;
        }
    }

    protected function tearDown(): void
    {
        if (isset($_SESSION)) {
            $_SESSION = [];
        }
        parent::tearDown();
    }
}

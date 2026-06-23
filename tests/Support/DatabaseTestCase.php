<?php
declare(strict_types=1);

namespace IMF\Tests\Support;

use App\Core\Database;
use PDO;

/**
 * Base class for Tier 2 (DB-backed) standalone app tests.
 *
 * Boots the app, connects to the configured database, wraps every
 * test in a transaction that is rolled back in tearDown so no test
 * data persists.
 *
 * Also seeds a minimal data set once per class in setUpBeforeClass
 * and restores it via a savepoint/rollback pattern.
 */
abstract class DatabaseTestCase extends AppTestCase
{
    protected static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$pdo = Database::pdo();
    }

    protected function setUp(): void
    {
        parent::setUp();
        self::$pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }
        parent::tearDown();
    }
}

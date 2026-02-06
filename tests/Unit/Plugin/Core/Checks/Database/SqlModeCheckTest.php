<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Database;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\SqlModeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SqlModeCheck::class)]
class SqlModeCheckTest extends TestCase
{
    private SqlModeCheck $sqlModeCheck;

    protected function setUp(): void
    {
        $this->sqlModeCheck = new SqlModeCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.sql_mode', $this->sqlModeCheck->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->sqlModeCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->sqlModeCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->sqlModeCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->sqlModeCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenSqlModeEmpty(): void
    {
        $database = MockDatabaseFactory::createWithResult('');
        $this->sqlModeCheck->setDatabase($database);

        $healthCheckResult = $this->sqlModeCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('empty', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenOnlyFullGroupByEnabled(): void
    {
        $database = MockDatabaseFactory::createWithResult('ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES');
        $this->sqlModeCheck->setDatabase($database);

        $healthCheckResult = $this->sqlModeCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('ONLY_FULL_GROUP_BY', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenSafeModesEnabled(): void
    {
        $database = MockDatabaseFactory::createWithResult('TRADITIONAL');
        $this->sqlModeCheck->setDatabase($database);

        $healthCheckResult = $this->sqlModeCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('TRADITIONAL', $healthCheckResult->description);
    }
}

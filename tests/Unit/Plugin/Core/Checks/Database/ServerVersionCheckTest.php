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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\ServerVersionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ServerVersionCheck::class)]
class ServerVersionCheckTest extends TestCase
{
    private ServerVersionCheck $serverVersionCheck;

    protected function setUp(): void
    {
        $this->serverVersionCheck = new ServerVersionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.server_version', $this->serverVersionCheck->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->serverVersionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->serverVersionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->serverVersionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->serverVersionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithMysqlMeetsRequirements(): void
    {
        $database = MockDatabaseFactory::createWithVersion('8.0.30');
        $this->serverVersionCheck->setDatabase($database);

        $healthCheckResult = $this->serverVersionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('MySQL', $healthCheckResult->description);
        $this->assertStringContainsString('8.0.30', $healthCheckResult->description);
    }

    public function testRunWithOldMysqlReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithVersion('5.7.44');
        $this->serverVersionCheck->setDatabase($database);

        $healthCheckResult = $this->serverVersionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('below recommended', $healthCheckResult->description);
    }

    public function testRunWithMariaDbMeetsRequirements(): void
    {
        $database = MockDatabaseFactory::createWithVersion('10.6.15-MariaDB');
        $this->serverVersionCheck->setDatabase($database);

        $healthCheckResult = $this->serverVersionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('MariaDB', $healthCheckResult->description);
    }

    public function testRunWithOldMariaDbReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithVersion('10.3.39-MariaDB');
        $this->serverVersionCheck->setDatabase($database);

        $healthCheckResult = $this->serverVersionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('MariaDB', $healthCheckResult->description);
        $this->assertStringContainsString('below recommended', $healthCheckResult->description);
    }
}

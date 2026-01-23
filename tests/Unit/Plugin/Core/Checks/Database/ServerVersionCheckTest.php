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
    private ServerVersionCheck $check;

    protected function setUp(): void
    {
        $this->check = new ServerVersionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.server_version', $this->check->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->check->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->check->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunWithMysqlMeetsRequirements(): void
    {
        $database = MockDatabaseFactory::createWithVersion('8.0.30');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('MySQL', $result->description);
        $this->assertStringContainsString('8.0.30', $result->description);
    }

    public function testRunWithOldMysqlReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithVersion('5.7.44');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('below recommended', $result->description);
    }

    public function testRunWithMariaDbMeetsRequirements(): void
    {
        $database = MockDatabaseFactory::createWithVersion('10.6.15-MariaDB');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('MariaDB', $result->description);
    }

    public function testRunWithOldMariaDbReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithVersion('10.3.39-MariaDB');
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('MariaDB', $result->description);
        $this->assertStringContainsString('below recommended', $result->description);
    }
}

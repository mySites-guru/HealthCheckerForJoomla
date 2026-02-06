<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Content;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Content\MenuOrphansCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MenuOrphansCheck::class)]
class MenuOrphansCheckTest extends TestCase
{
    private MenuOrphansCheck $menuOrphansCheck;

    protected function setUp(): void
    {
        $this->menuOrphansCheck = new MenuOrphansCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('content.menu_orphans', $this->menuOrphansCheck->getSlug());
    }

    public function testGetCategoryReturnsContent(): void
    {
        $this->assertSame('content', $this->menuOrphansCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->menuOrphansCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->menuOrphansCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->menuOrphansCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenNoOrphanedMenuItems(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->menuOrphansCheck->setDatabase($database);

        $healthCheckResult = $this->menuOrphansCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('existing content', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenOrphanedMenuItemsExist(): void
    {
        $database = MockDatabaseFactory::createWithResult(3);
        $this->menuOrphansCheck->setDatabase($database);

        $healthCheckResult = $this->menuOrphansCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('3 menu item(s)', $healthCheckResult->description);
        $this->assertStringContainsString('404', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenSingleOrphanedMenuItemExists(): void
    {
        $database = MockDatabaseFactory::createWithResult(1);
        $this->menuOrphansCheck->setDatabase($database);

        $healthCheckResult = $this->menuOrphansCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 menu item(s)', $healthCheckResult->description);
    }

    public function testRunFallsBackToSubstringIndexOnRegexpError(): void
    {
        // First query throws exception (REGEXP_SUBSTR not supported), second succeeds with SUBSTRING_INDEX
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'exception' => new \RuntimeException('REGEXP_SUBSTR not supported'),
            ],
            [
                'method' => 'loadResult',
                'return' => 2,
            ],
        ]);
        $this->menuOrphansCheck->setDatabase($database);

        $healthCheckResult = $this->menuOrphansCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('2 menu item(s)', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWithFallbackWhenNoOrphans(): void
    {
        // First query throws exception (REGEXP_SUBSTR not supported), second succeeds with 0 orphans
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'exception' => new \RuntimeException('REGEXP_SUBSTR not supported'),
            ],
            [
                'method' => 'loadResult',
                'return' => 0,
            ],
        ]);
        $this->menuOrphansCheck->setDatabase($database);

        $healthCheckResult = $this->menuOrphansCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('existing content', $healthCheckResult->description);
    }
}

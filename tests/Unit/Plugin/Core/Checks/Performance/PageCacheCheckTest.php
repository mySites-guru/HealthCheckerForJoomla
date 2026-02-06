<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Performance;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use Joomla\CMS\Plugin\PluginHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\PageCacheCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PageCacheCheck::class)]
class PageCacheCheckTest extends TestCase
{
    private PageCacheCheck $pageCacheCheck;

    protected function setUp(): void
    {
        PluginHelper::resetEnabled();
        $this->pageCacheCheck = new PageCacheCheck();
    }

    protected function tearDown(): void
    {
        PluginHelper::resetEnabled();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.page_cache', $this->pageCacheCheck->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $this->assertSame('performance', $this->pageCacheCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->pageCacheCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->pageCacheCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsWarningWhenPluginDisabled(): void
    {
        // PluginHelper::isEnabled returns false by default in stub
        $healthCheckResult = $this->pageCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenPluginEnabled(): void
    {
        // Set the page cache plugin as enabled
        PluginHelper::setEnabled('system', 'cache', true);

        // Inject database with browser cache enabled
        $params = json_encode([
            'browsercache' => 1,
        ]);
        $database = MockDatabaseFactory::createWithResult($params);
        $this->pageCacheCheck->setDatabase($database);

        $healthCheckResult = $this->pageCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('enabled', $healthCheckResult->description);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // Test with plugin disabled
        $result = $this->pageCacheCheck->run();
        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);

        // Test with plugin enabled and browser cache enabled
        PluginHelper::setEnabled('system', 'cache', true);
        $params = json_encode([
            'browsercache' => 1,
        ]);
        $database = MockDatabaseFactory::createWithResult($params);
        $this->pageCacheCheck->setDatabase($database);

        $result = $this->pageCacheCheck->run();
        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }
}

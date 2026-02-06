<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Performance;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\SystemCacheCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SystemCacheCheck::class)]
class SystemCacheCheckTest extends TestCase
{
    private SystemCacheCheck $systemCacheCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        Factory::setApplication($this->cmsApplication);
        $this->systemCacheCheck = new SystemCacheCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.system_cache', $this->systemCacheCheck->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $this->assertSame('performance', $this->systemCacheCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->systemCacheCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->systemCacheCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithCachingEnabledReturnsGood(): void
    {
        $this->cmsApplication->set('caching', 1);

        $healthCheckResult = $this->systemCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('enabled', $healthCheckResult->description);
    }

    public function testRunWithProgressiveCachingReturnsGood(): void
    {
        $this->cmsApplication->set('caching', 2);

        $healthCheckResult = $this->systemCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithCachingDisabledReturnsWarning(): void
    {
        $this->cmsApplication->set('caching', 0);

        $healthCheckResult = $this->systemCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', $healthCheckResult->description);
    }

    public function testRunReportsCacheHandler(): void
    {
        $this->cmsApplication->set('caching', 1);
        $this->cmsApplication->set('cache_handler', 'file');

        $healthCheckResult = $this->systemCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('file', $healthCheckResult->description);
    }

    public function testRunWithRedisHandler(): void
    {
        $this->cmsApplication->set('caching', 1);
        $this->cmsApplication->set('cache_handler', 'redis');

        $healthCheckResult = $this->systemCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('redis', $healthCheckResult->description);
    }
}

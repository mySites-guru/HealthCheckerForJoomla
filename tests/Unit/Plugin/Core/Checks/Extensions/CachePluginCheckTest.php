<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Extensions;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\CachePluginCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CachePluginCheck::class)]
class CachePluginCheckTest extends TestCase
{
    private CachePluginCheck $cachePluginCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        Factory::setApplication($this->cmsApplication);
        $this->cachePluginCheck = new CachePluginCheck();
        PluginHelper::resetEnabled();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
        PluginHelper::resetEnabled();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.cache_plugin', $this->cachePluginCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->cachePluginCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->cachePluginCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->cachePluginCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsWarningWhenBothDisabled(): void
    {
        // Both plugin and system cache disabled
        $this->cmsApplication->set('caching', 0);
        PluginHelper::setEnabled('system', 'cache', false);

        $healthCheckResult = $this->cachePluginCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenSystemCacheEnabledButPluginDisabled(): void
    {
        // System cache enabled but plugin disabled - basic caching works
        $this->cmsApplication->set('caching', 1);
        PluginHelper::setEnabled('system', 'cache', false);

        $healthCheckResult = $this->cachePluginCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('System caching is enabled', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenPluginEnabledButSystemCacheDisabled(): void
    {
        // Plugin enabled but system caching is off - plugin will not function
        $this->cmsApplication->set('caching', 0);
        PluginHelper::setEnabled('system', 'cache', true);

        $healthCheckResult = $this->cachePluginCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString(
            'plugin is enabled but system caching is disabled',
            $healthCheckResult->description,
        );
    }

    public function testRunReturnsGoodWhenBothEnabled(): void
    {
        // Both enabled - optimal configuration
        $this->cmsApplication->set('caching', 1);
        $this->cmsApplication->set('cache_handler', 'file');
        $this->cmsApplication->set('cachetime', 30);
        PluginHelper::setEnabled('system', 'cache', true);

        $healthCheckResult = $this->cachePluginCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Page cache plugin is enabled', $healthCheckResult->description);
        $this->assertStringContainsString('file', $healthCheckResult->description);
        $this->assertStringContainsString('30', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWithMemcachedHandler(): void
    {
        $this->cmsApplication->set('caching', 1);
        $this->cmsApplication->set('cache_handler', 'memcached');
        $this->cmsApplication->set('cachetime', 60);
        PluginHelper::setEnabled('system', 'cache', true);

        $healthCheckResult = $this->cachePluginCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('memcached', $healthCheckResult->description);
        $this->assertStringContainsString('60 minutes', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWithRedisHandler(): void
    {
        $this->cmsApplication->set('caching', 1);
        $this->cmsApplication->set('cache_handler', 'redis');
        $this->cmsApplication->set('cachetime', 15);
        PluginHelper::setEnabled('system', 'cache', true);

        $healthCheckResult = $this->cachePluginCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('redis', $healthCheckResult->description);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // This check never returns critical status
        $healthCheckResult = $this->cachePluginCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugins\Core\Checks\Extensions;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\CachePluginCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CachePluginCheck::class)]
class CachePluginCheckTest extends TestCase
{
    public function testGetSlugReturnsCorrectValue(): void
    {
        $cachePluginCheck = new CachePluginCheck();
        $this->assertSame('extensions.cache_plugin', $cachePluginCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $cachePluginCheck = new CachePluginCheck();
        $this->assertSame('extensions', $cachePluginCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $cachePluginCheck = new CachePluginCheck();
        $this->assertSame('core', $cachePluginCheck->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $cachePluginCheck = new CachePluginCheck();
        $healthCheckResult = $cachePluginCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame('extensions.cache_plugin', $healthCheckResult->slug);
        $this->assertSame('extensions', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsGoodOrWarningStatus(): void
    {
        $cachePluginCheck = new CachePluginCheck();
        $healthCheckResult = $cachePluginCheck->run();

        // Cache plugin check only returns Good or Warning, never Critical
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning],
            'Cache plugin check should return Good or Warning status',
        );
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $cachePluginCheck = new CachePluginCheck();
        $healthCheckResult = $cachePluginCheck->run();

        $this->assertNotEmpty($healthCheckResult->description);
    }

    public function testResultDescriptionMentionsCacheOrPlugin(): void
    {
        $cachePluginCheck = new CachePluginCheck();
        $healthCheckResult = $cachePluginCheck->run();

        // Description should mention cache or plugin
        $this->assertTrue(
            stripos($healthCheckResult->description, 'cache') !== false ||
            stripos($healthCheckResult->description, 'plugin') !== false,
            'Description should mention cache or plugin',
        );
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $cachePluginCheck = new CachePluginCheck();

        // Database should be null (not injected)
        $this->assertNull($cachePluginCheck->getDatabase());

        // Check should still work without database
        $healthCheckResult = $cachePluginCheck->run();
        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $cachePluginCheck = new CachePluginCheck();

        $healthCheckResult = $cachePluginCheck->run();
        $result2 = $cachePluginCheck->run();

        // Results should be the same since config doesn't change during test
        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $cachePluginCheck = new CachePluginCheck();
        $healthCheckResult = $cachePluginCheck->run();

        $array = $healthCheckResult->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('provider', $array);
    }
}

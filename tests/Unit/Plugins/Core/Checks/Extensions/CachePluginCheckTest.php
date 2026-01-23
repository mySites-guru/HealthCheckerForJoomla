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
        $check = new CachePluginCheck();
        $this->assertSame('extensions.cache_plugin', $check->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $check = new CachePluginCheck();
        $this->assertSame('extensions', $check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $check = new CachePluginCheck();
        $this->assertSame('core', $check->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $check = new CachePluginCheck();
        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame('extensions.cache_plugin', $result->slug);
        $this->assertSame('extensions', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsGoodOrWarningStatus(): void
    {
        $check = new CachePluginCheck();
        $result = $check->run();

        // Cache plugin check only returns Good or Warning, never Critical
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning],
            'Cache plugin check should return Good or Warning status',
        );
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $check = new CachePluginCheck();
        $result = $check->run();

        $this->assertNotEmpty($result->description);
    }

    public function testResultDescriptionMentionsCacheOrPlugin(): void
    {
        $check = new CachePluginCheck();
        $result = $check->run();

        // Description should mention cache or plugin
        $this->assertTrue(
            stripos($result->description, 'cache') !== false ||
            stripos($result->description, 'plugin') !== false,
            'Description should mention cache or plugin',
        );
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $check = new CachePluginCheck();

        // Database should be null (not injected)
        $this->assertNull($check->getDatabase());

        // Check should still work without database
        $result = $check->run();
        $this->assertInstanceOf(HealthCheckResult::class, $result);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $check = new CachePluginCheck();

        $result1 = $check->run();
        $result2 = $check->run();

        // Results should be the same since config doesn't change during test
        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        $this->assertSame($result1->description, $result2->description);
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $check = new CachePluginCheck();
        $result = $check->run();

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('provider', $array);
    }
}

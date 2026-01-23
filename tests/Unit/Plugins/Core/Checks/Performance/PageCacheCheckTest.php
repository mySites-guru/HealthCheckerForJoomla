<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugins\Core\Checks\Performance;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\PageCacheCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PageCacheCheck::class)]
class PageCacheCheckTest extends TestCase
{
    public function testGetSlugReturnsCorrectValue(): void
    {
        $check = new PageCacheCheck();
        $this->assertSame('performance.page_cache', $check->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $check = new PageCacheCheck();
        $this->assertSame('performance', $check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $check = new PageCacheCheck();
        $this->assertSame('core', $check->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $check = new PageCacheCheck();
        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame('performance.page_cache', $result->slug);
        $this->assertSame('performance', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsGoodOrWarningStatus(): void
    {
        $check = new PageCacheCheck();
        $result = $check->run();

        // Page cache check only returns Good or Warning, never Critical
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning],
            'Page cache check should return Good or Warning status',
        );
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $check = new PageCacheCheck();
        $result = $check->run();

        $this->assertNotEmpty($result->description);
    }

    public function testResultDescriptionMentionsCache(): void
    {
        $check = new PageCacheCheck();
        $result = $check->run();

        $this->assertStringContainsStringIgnoringCase('cache', $result->description);
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $check = new PageCacheCheck();

        // Database should be null (not injected)
        $this->assertNull($check->getDatabase());

        // Check should still work without database
        $result = $check->run();
        $this->assertInstanceOf(HealthCheckResult::class, $result);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $check = new PageCacheCheck();

        $result1 = $check->run();
        $result2 = $check->run();

        // Results should be the same since plugin state doesn't change during test
        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        $this->assertSame($result1->description, $result2->description);
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $check = new PageCacheCheck();
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

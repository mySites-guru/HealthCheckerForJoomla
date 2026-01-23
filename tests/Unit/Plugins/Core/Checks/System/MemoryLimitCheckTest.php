<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugins\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\MemoryLimitCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MemoryLimitCheck::class)]
class MemoryLimitCheckTest extends TestCase
{
    public function testGetSlugReturnsCorrectValue(): void
    {
        $check = new MemoryLimitCheck();
        $this->assertSame('system.memory_limit', $check->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $check = new MemoryLimitCheck();
        $this->assertSame('system', $check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $check = new MemoryLimitCheck();
        $this->assertSame('core', $check->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $check = new MemoryLimitCheck();
        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame('system.memory_limit', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $check = new MemoryLimitCheck();
        $result = $check->run();

        $this->assertNotEmpty($result->description);
    }

    public function testResultDescriptionMentionsMemory(): void
    {
        $check = new MemoryLimitCheck();
        $result = $check->run();

        $this->assertStringContainsStringIgnoringCase('memory', $result->description);
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $check = new MemoryLimitCheck();

        // Database should be null (not injected)
        $this->assertNull($check->getDatabase());

        // Check should still work without database
        $result = $check->run();
        $this->assertInstanceOf(HealthCheckResult::class, $result);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $check = new MemoryLimitCheck();

        $result1 = $check->run();
        $result2 = $check->run();

        // Results should be the same since memory_limit doesn't change during test
        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        $this->assertSame($result1->description, $result2->description);
    }

    public function testCheckReturnsValidStatusForCurrentEnvironment(): void
    {
        $check = new MemoryLimitCheck();
        $result = $check->run();

        // Check can return Good, Warning, or Critical based on memory_limit
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $check = new MemoryLimitCheck();
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

    public function testCurrentEnvironmentHasSufficientMemory(): void
    {
        // In most test environments, memory limit should be adequate
        $memoryLimit = ini_get('memory_limit');

        $check = new MemoryLimitCheck();
        $result = $check->run();

        if ($memoryLimit === '-1') {
            // Unlimited memory
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
            $this->assertStringContainsStringIgnoringCase('unlimited', $result->description);
        } else {
            // Memory is limited, check that a valid status is returned
            $this->assertContains(
                $result->healthStatus,
                [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
            );
        }
    }
}

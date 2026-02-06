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
        $memoryLimitCheck = new MemoryLimitCheck();
        $this->assertSame('system.memory_limit', $memoryLimitCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $memoryLimitCheck = new MemoryLimitCheck();
        $this->assertSame('system', $memoryLimitCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $memoryLimitCheck = new MemoryLimitCheck();
        $this->assertSame('core', $memoryLimitCheck->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $memoryLimitCheck = new MemoryLimitCheck();
        $healthCheckResult = $memoryLimitCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame('system.memory_limit', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $memoryLimitCheck = new MemoryLimitCheck();
        $healthCheckResult = $memoryLimitCheck->run();

        $this->assertNotEmpty($healthCheckResult->description);
    }

    public function testResultDescriptionMentionsMemory(): void
    {
        $memoryLimitCheck = new MemoryLimitCheck();
        $healthCheckResult = $memoryLimitCheck->run();

        $this->assertStringContainsStringIgnoringCase('memory', $healthCheckResult->description);
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $memoryLimitCheck = new MemoryLimitCheck();

        // Database should be null (not injected)
        $this->assertNull($memoryLimitCheck->getDatabase());

        // Check should still work without database
        $healthCheckResult = $memoryLimitCheck->run();
        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $memoryLimitCheck = new MemoryLimitCheck();

        $healthCheckResult = $memoryLimitCheck->run();
        $result2 = $memoryLimitCheck->run();

        // Results should be the same since memory_limit doesn't change during test
        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testCheckReturnsValidStatusForCurrentEnvironment(): void
    {
        $memoryLimitCheck = new MemoryLimitCheck();
        $healthCheckResult = $memoryLimitCheck->run();

        // Check can return Good, Warning, or Critical based on memory_limit
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $memoryLimitCheck = new MemoryLimitCheck();
        $healthCheckResult = $memoryLimitCheck->run();

        $array = $healthCheckResult->toArray();

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

        $memoryLimitCheck = new MemoryLimitCheck();
        $healthCheckResult = $memoryLimitCheck->run();

        if ($memoryLimit === '-1') {
            // Unlimited memory
            $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
            $this->assertStringContainsStringIgnoringCase('unlimited', $healthCheckResult->description);
        } else {
            // Memory is limited, check that a valid status is returned
            $this->assertContains(
                $healthCheckResult->healthStatus,
                [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
            );
        }
    }
}

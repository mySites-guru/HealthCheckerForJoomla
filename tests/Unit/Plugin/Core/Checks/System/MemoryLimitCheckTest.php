<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\MemoryLimitCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MemoryLimitCheck::class)]
class MemoryLimitCheckTest extends TestCase
{
    private MemoryLimitCheck $memoryLimitCheck;

    private string $originalMemoryLimit;

    protected function setUp(): void
    {
        $this->memoryLimitCheck = new MemoryLimitCheck();
        $this->originalMemoryLimit = ini_get('memory_limit');
    }

    protected function tearDown(): void
    {
        // Restore original value
        ini_set('memory_limit', $this->originalMemoryLimit);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.memory_limit', $this->memoryLimitCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->memoryLimitCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->memoryLimitCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->memoryLimitCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->memoryLimitCheck->run();

        $this->assertSame('system.memory_limit', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $healthCheckResult = $this->memoryLimitCheck->run();

        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsMemoryInfo(): void
    {
        $healthCheckResult = $this->memoryLimitCheck->run();

        // Description should mention memory limit
        $this->assertTrue(
            str_contains(strtolower($healthCheckResult->description), 'memory') ||
            str_contains(strtolower($healthCheckResult->description), 'unlimited'),
        );
    }

    public function testReturnsGoodWhenUnlimited(): void
    {
        // Set memory_limit to -1 (unlimited)
        ini_set('memory_limit', '-1');

        $healthCheckResult = $this->memoryLimitCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('unlimited', $healthCheckResult->description);
    }

    public function testReturnsCriticalWhenBelowMinimum(): void
    {
        // Set memory_limit to 64M (below 128M minimum)
        ini_set('memory_limit', '64M');

        $healthCheckResult = $this->memoryLimitCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('64M', $healthCheckResult->description);
        $this->assertStringContainsString('128M', $healthCheckResult->description);
    }

    public function testReturnsWarningWhenBelowRecommended(): void
    {
        // Set memory_limit to 192M (above 128M minimum but below 256M recommended)
        ini_set('memory_limit', '192M');

        $healthCheckResult = $this->memoryLimitCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('192M', $healthCheckResult->description);
        $this->assertStringContainsString('256M', $healthCheckResult->description);
    }

    public function testReturnsGoodWhenMeetsRequirements(): void
    {
        // Set memory_limit to 512M (above recommended)
        ini_set('memory_limit', '512M');

        $healthCheckResult = $this->memoryLimitCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('512M', $healthCheckResult->description);
        $this->assertStringContainsString('meets requirements', $healthCheckResult->description);
    }

    public function testReturnsGoodAtExactlyRecommended(): void
    {
        // Set memory_limit to exactly 256M (recommended)
        ini_set('memory_limit', '256M');

        $healthCheckResult = $this->memoryLimitCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('meets requirements', $healthCheckResult->description);
    }

    public function testReturnsWarningAtExactlyMinimum(): void
    {
        // Set memory_limit to exactly 128M (minimum)
        ini_set('memory_limit', '128M');

        $healthCheckResult = $this->memoryLimitCheck->run();

        // 128M is at minimum but below recommended, so Warning
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testConvertToBytesWithKilobytes(): void
    {
        // Test that K suffix is handled correctly
        // 131072K = 128M (minimum)
        ini_set('memory_limit', '131072K');

        $healthCheckResult = $this->memoryLimitCheck->run();

        // Should be Warning (at minimum, below recommended)
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testConvertToBytesWithGigabytes(): void
    {
        // Test that G suffix is handled correctly
        // 1G = 1024M (well above recommended)
        ini_set('memory_limit', '1G');

        $healthCheckResult = $this->memoryLimitCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1G', $healthCheckResult->description);
    }

    public function testConvertToBytesWithPlainBytes(): void
    {
        // Test with plain bytes (no suffix)
        // 134217728 = 128M (minimum)
        ini_set('memory_limit', '134217728');

        $healthCheckResult = $this->memoryLimitCheck->run();

        // Should be Warning (at minimum, below recommended)
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testConvertToBytesLowercaseSuffix(): void
    {
        // Test lowercase suffix
        ini_set('memory_limit', '256m');

        $healthCheckResult = $this->memoryLimitCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->memoryLimitCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        ini_set('memory_limit', '256M');

        $healthCheckResult = $this->memoryLimitCheck->run();
        $result2 = $this->memoryLimitCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testDescriptionIncludesCurrentValue(): void
    {
        $memoryLimit = ini_get('memory_limit');
        $healthCheckResult = $this->memoryLimitCheck->run();

        // Description should include the current value or "unlimited"
        if ($memoryLimit === '-1') {
            $this->assertStringContainsString('unlimited', $healthCheckResult->description);
        } else {
            $this->assertStringContainsString($memoryLimit, $healthCheckResult->description);
        }
    }

    public function testBoundaryJustBelowMinimum(): void
    {
        // 127M is just below 128M minimum
        ini_set('memory_limit', '127M');

        $healthCheckResult = $this->memoryLimitCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testBoundaryJustBelowRecommended(): void
    {
        // 255M is just below 256M recommended
        ini_set('memory_limit', '255M');

        $healthCheckResult = $this->memoryLimitCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testVeryHighMemoryLimit(): void
    {
        // 2G is very high
        ini_set('memory_limit', '2G');

        $healthCheckResult = $this->memoryLimitCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }
}

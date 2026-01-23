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
    private MemoryLimitCheck $check;

    private string $originalMemoryLimit;

    protected function setUp(): void
    {
        $this->check = new MemoryLimitCheck();
        $this->originalMemoryLimit = ini_get('memory_limit');
    }

    protected function tearDown(): void
    {
        // Restore original value
        ini_set('memory_limit', $this->originalMemoryLimit);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.memory_limit', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->check->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->check->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.memory_limit', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsMemoryInfo(): void
    {
        $result = $this->check->run();

        // Description should mention memory limit
        $this->assertTrue(
            str_contains(strtolower($result->description), 'memory') ||
            str_contains(strtolower($result->description), 'unlimited'),
        );
    }

    public function testReturnsGoodWhenUnlimited(): void
    {
        // Set memory_limit to -1 (unlimited)
        ini_set('memory_limit', '-1');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('unlimited', $result->description);
    }

    public function testReturnsCriticalWhenBelowMinimum(): void
    {
        // Set memory_limit to 64M (below 128M minimum)
        ini_set('memory_limit', '64M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('64M', $result->description);
        $this->assertStringContainsString('128M', $result->description);
    }

    public function testReturnsWarningWhenBelowRecommended(): void
    {
        // Set memory_limit to 192M (above 128M minimum but below 256M recommended)
        ini_set('memory_limit', '192M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('192M', $result->description);
        $this->assertStringContainsString('256M', $result->description);
    }

    public function testReturnsGoodWhenMeetsRequirements(): void
    {
        // Set memory_limit to 512M (above recommended)
        ini_set('memory_limit', '512M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('512M', $result->description);
        $this->assertStringContainsString('meets requirements', $result->description);
    }

    public function testReturnsGoodAtExactlyRecommended(): void
    {
        // Set memory_limit to exactly 256M (recommended)
        ini_set('memory_limit', '256M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('meets requirements', $result->description);
    }

    public function testReturnsWarningAtExactlyMinimum(): void
    {
        // Set memory_limit to exactly 128M (minimum)
        ini_set('memory_limit', '128M');

        $result = $this->check->run();

        // 128M is at minimum but below recommended, so Warning
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testConvertToBytesWithKilobytes(): void
    {
        // Test that K suffix is handled correctly
        // 131072K = 128M (minimum)
        ini_set('memory_limit', '131072K');

        $result = $this->check->run();

        // Should be Warning (at minimum, below recommended)
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testConvertToBytesWithGigabytes(): void
    {
        // Test that G suffix is handled correctly
        // 1G = 1024M (well above recommended)
        ini_set('memory_limit', '1G');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('1G', $result->description);
    }

    public function testConvertToBytesWithPlainBytes(): void
    {
        // Test with plain bytes (no suffix)
        // 134217728 = 128M (minimum)
        ini_set('memory_limit', '134217728');

        $result = $this->check->run();

        // Should be Warning (at minimum, below recommended)
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testConvertToBytesLowercaseSuffix(): void
    {
        // Test lowercase suffix
        ini_set('memory_limit', '256m');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        ini_set('memory_limit', '256M');

        $result1 = $this->check->run();
        $result2 = $this->check->run();

        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        $this->assertSame($result1->description, $result2->description);
    }

    public function testDescriptionIncludesCurrentValue(): void
    {
        $memoryLimit = ini_get('memory_limit');
        $result = $this->check->run();

        // Description should include the current value or "unlimited"
        if ($memoryLimit === '-1') {
            $this->assertStringContainsString('unlimited', $result->description);
        } else {
            $this->assertStringContainsString($memoryLimit, $result->description);
        }
    }

    public function testBoundaryJustBelowMinimum(): void
    {
        // 127M is just below 128M minimum
        ini_set('memory_limit', '127M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testBoundaryJustBelowRecommended(): void
    {
        // 255M is just below 256M recommended
        ini_set('memory_limit', '255M');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testVeryHighMemoryLimit(): void
    {
        // 2G is very high
        ini_set('memory_limit', '2G');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }
}

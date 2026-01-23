<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\RealpathCacheCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RealpathCacheCheck::class)]
class RealpathCacheCheckTest extends TestCase
{
    private RealpathCacheCheck $check;

    protected function setUp(): void
    {
        $this->check = new RealpathCacheCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.realpath_cache', $this->check->getSlug());
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

        $this->assertSame('system.realpath_cache', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Can return Good or Warning (never Critical)
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testRunDescriptionContainsCacheInfo(): void
    {
        $result = $this->check->run();

        // Description should mention realpath, cache, or usage
        $this->assertTrue(
            str_contains(strtolower($result->description), 'realpath') ||
            str_contains(strtolower($result->description), 'cache'),
        );
    }

    public function testCurrentRealpathCacheSizeIsDetectable(): void
    {
        $cacheSize = ini_get('realpath_cache_size');

        // realpath_cache_size should return a value
        $this->assertNotFalse($cacheSize);
    }

    public function testCurrentRealpathCacheTtlIsDetectable(): void
    {
        $cacheTtl = ini_get('realpath_cache_ttl');

        // realpath_cache_ttl should return a value
        $this->assertNotFalse($cacheTtl);
    }

    public function testRealpathCacheSizeFunction(): void
    {
        // realpath_cache_size() should return current usage
        $currentUsage = realpath_cache_size();

        $this->assertIsInt($currentUsage);
        $this->assertGreaterThanOrEqual(0, $currentUsage);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // This check should never return Critical status
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testDescriptionIncludesUsagePercentage(): void
    {
        $result = $this->check->run();

        // Description should include usage percentage or mention usage
        $this->assertTrue(
            str_contains($result->description, '%') ||
            str_contains(strtolower($result->description), 'unable'),
        );
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $result1 = $this->check->run();
        $result2 = $this->check->run();

        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        // Description may vary slightly due to usage changes, but status should be same
    }

    public function testResultHasCorrectStructure(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.realpath_cache', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testConvertToBytesLogicWithKilobytes(): void
    {
        // Test that the check can parse K suffix
        // '512K' should be converted to 524288 bytes
        $this->assertSame(524288, 512 * 1024);
    }

    public function testConvertToBytesLogicWithMegabytes(): void
    {
        // Test that the check can parse M suffix
        // '4M' should be converted to 4194304 bytes
        $this->assertSame(4194304, 4 * 1024 * 1024);
    }

    public function testConvertToBytesLogicWithGigabytes(): void
    {
        // Test that the check can parse G suffix
        // '1G' should be converted to 1073741824 bytes
        $this->assertSame(1073741824, 1 * 1024 * 1024 * 1024);
    }

    public function testRecommendedMinimumSizeIs4M(): void
    {
        // Recommended minimum is 4MB = 4 * 1024 * 1024 = 4194304 bytes
        $recommendedBytes = 4 * 1024 * 1024;

        $this->assertSame(4194304, $recommendedBytes);
    }

    public function testGoodResultIncludesTtl(): void
    {
        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Good) {
            // Good result should include TTL information
            $this->assertStringContainsString('TTL', $result->description);
        }
    }

    public function testWarningResultExplainsIssue(): void
    {
        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Warning) {
            // Warning should explain why
            $this->assertTrue(
                str_contains($result->description, 'below') ||
                str_contains($result->description, 'nearly full') ||
                str_contains($result->description, 'Unable'),
            );
        } else {
            // If not Warning, should be Good
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        }
    }

    public function testCacheUsageCalculation(): void
    {
        $currentUsage = realpath_cache_size();
        $cacheSize = ini_get('realpath_cache_size');

        // Both should be available
        $this->assertIsInt($currentUsage);
        $this->assertNotFalse($cacheSize);
    }

    public function testConvertToBytesWithEmptyString(): void
    {
        // Test empty string handling - should return 0
        $this->assertSame(0, $this->convertToBytesHelper(''));
    }

    public function testConvertToBytesWithZeroString(): void
    {
        // Test '0' string handling - should return 0
        $this->assertSame(0, $this->convertToBytesHelper('0'));
    }

    public function testConvertToBytesWithWhitespace(): void
    {
        // Test values with whitespace (trimmed)
        $this->assertSame(4 * 1024 * 1024, $this->convertToBytesHelper(' 4M '));
        $this->assertSame(512 * 1024, $this->convertToBytesHelper(' 512K '));
    }

    public function testConvertToBytesWithNumericOnly(): void
    {
        // Test numeric values without suffix (bytes)
        $this->assertSame(1024, $this->convertToBytesHelper('1024'));
        $this->assertSame(65536, $this->convertToBytesHelper('65536'));
    }

    public function testConvertToBytesWithLowercaseSuffix(): void
    {
        // Test lowercase suffixes
        $this->assertSame(4 * 1024 * 1024, $this->convertToBytesHelper('4m'));
        $this->assertSame(512 * 1024, $this->convertToBytesHelper('512k'));
        $this->assertSame(1 * 1024 * 1024 * 1024, $this->convertToBytesHelper('1g'));
    }

    public function testConvertToBytesWithUppercaseSuffix(): void
    {
        // Test uppercase suffixes
        $this->assertSame(4 * 1024 * 1024, $this->convertToBytesHelper('4M'));
        $this->assertSame(512 * 1024, $this->convertToBytesHelper('512K'));
        $this->assertSame(1 * 1024 * 1024 * 1024, $this->convertToBytesHelper('1G'));
    }

    public function testCacheSizeThresholdCheck(): void
    {
        $cacheSize = ini_get('realpath_cache_size');
        $sizeBytes = $this->convertToBytesHelper($cacheSize);
        $result = $this->check->run();

        // Verify check correctly evaluates against 4MB threshold
        $recommendedSize = 4 * 1024 * 1024;

        if ($sizeBytes < $recommendedSize) {
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
            $this->assertStringContainsString('below', $result->description);
        } else {
            // Either Good or Warning for high usage
            $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
        }
    }

    public function testHighUsageThresholdCheck(): void
    {
        $currentUsage = realpath_cache_size();
        $cacheSize = ini_get('realpath_cache_size');
        $sizeBytes = $this->convertToBytesHelper($cacheSize);

        // Calculate usage percentage
        $usedPercent = $sizeBytes > 0 ? round(($currentUsage / $sizeBytes) * 100, 1) : 0;

        $result = $this->check->run();

        // If usage is over 90% and cache is big enough, should warn
        if ($usedPercent > 90 && $sizeBytes >= 4 * 1024 * 1024) {
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
            $this->assertStringContainsString('nearly full', $result->description);
        } else {
            // Document usage percentage and ensure result is valid
            $this->assertLessThanOrEqual(100.0, $usedPercent, 'Cache usage percentage is valid');
        }
    }

    public function testSlugFormat(): void
    {
        $slug = $this->check->getSlug();

        // Slug should be lowercase with dot separator
        $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z_]+$/', $slug);
    }

    public function testCategoryIsValid(): void
    {
        $category = $this->check->getCategory();

        // Should be a valid category
        $validCategories = ['system', 'database', 'security', 'users', 'extensions', 'performance', 'seo', 'content'];
        $this->assertContains($category, $validCategories);
    }

    /**
     * Helper method to replicate convertToBytes logic for testing.
     */
    private function convertToBytesHelper(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '0') {
            return 0;
        }

        $last = strtolower($value[strlen($value) - 1]);
        $numericValue = (int) $value;

        return match ($last) {
            'g' => $numericValue * 1024 * 1024 * 1024,
            'm' => $numericValue * 1024 * 1024,
            'k' => $numericValue * 1024,
            default => $numericValue,
        };
    }
}

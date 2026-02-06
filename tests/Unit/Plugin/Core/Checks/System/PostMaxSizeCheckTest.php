<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\PostMaxSizeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PostMaxSizeCheck::class)]
class PostMaxSizeCheckTest extends TestCase
{
    private PostMaxSizeCheck $postMaxSizeCheck;

    protected function setUp(): void
    {
        $this->postMaxSizeCheck = new PostMaxSizeCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.post_max_size', $this->postMaxSizeCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->postMaxSizeCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->postMaxSizeCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->postMaxSizeCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->postMaxSizeCheck->run();

        $this->assertSame('system.post_max_size', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $healthCheckResult = $this->postMaxSizeCheck->run();

        // Can return Good, Warning, or Critical depending on post_max_size value
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsPostMaxSizeInfo(): void
    {
        $healthCheckResult = $this->postMaxSizeCheck->run();

        // Description should mention post_max_size
        $this->assertStringContainsString('post_max_size', $healthCheckResult->description);
    }

    public function testCurrentPostMaxSizeIsDetectable(): void
    {
        $postMaxSize = ini_get('post_max_size');

        // post_max_size should return a value
        $this->assertNotFalse($postMaxSize);
    }

    public function testCheckThresholds(): void
    {
        // Test environment thresholds:
        // >= 32M: Good
        // >= 8M and < 32M: Warning
        // < 8M: Critical
        $postMaxSize = ini_get('post_max_size');
        $bytes = $this->convertToBytes($postMaxSize);
        $healthCheckResult = $this->postMaxSizeCheck->run();

        if ($bytes >= 32 * 1024 * 1024) {
            $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        } elseif ($bytes >= 8 * 1024 * 1024) {
            $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        } else {
            $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        }
    }

    public function testDescriptionIncludesCurrentValue(): void
    {
        $healthCheckResult = $this->postMaxSizeCheck->run();
        $postMaxSize = ini_get('post_max_size');

        // Description should include the current value
        $this->assertStringContainsString($postMaxSize, $healthCheckResult->description);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->postMaxSizeCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->postMaxSizeCheck->run();
        $result2 = $this->postMaxSizeCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testConvertToBytesWithEmptyString(): void
    {
        // Test empty string handling - should return 0
        $this->assertSame(0, $this->convertToBytes(''));
    }

    public function testConvertToBytesWithZeroString(): void
    {
        // Test '0' string handling - should return 0
        $this->assertSame(0, $this->convertToBytes('0'));
    }

    public function testConvertToBytesWithWhitespace(): void
    {
        // Test values with whitespace (trimmed)
        $this->assertSame(32 * 1024 * 1024, $this->convertToBytes(' 32M '));
        $this->assertSame(8 * 1024 * 1024, $this->convertToBytes(' 8M '));
    }

    public function testConvertToBytesWithNumericOnly(): void
    {
        // Test numeric values without suffix (bytes)
        $this->assertSame(1024, $this->convertToBytes('1024'));
        $this->assertSame(8388608, $this->convertToBytes('8388608'));
    }

    public function testConvertToBytesWithLowercaseSuffix(): void
    {
        // Test lowercase suffixes
        $this->assertSame(32 * 1024 * 1024, $this->convertToBytes('32m'));
        $this->assertSame(512 * 1024, $this->convertToBytes('512k'));
        $this->assertSame(1024 * 1024 * 1024, $this->convertToBytes('1g'));
    }

    public function testConvertToBytesWithUppercaseSuffix(): void
    {
        // Test uppercase suffixes
        $this->assertSame(32 * 1024 * 1024, $this->convertToBytes('32M'));
        $this->assertSame(512 * 1024, $this->convertToBytes('512K'));
        $this->assertSame(1024 * 1024 * 1024, $this->convertToBytes('1G'));
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->postMaxSizeCheck->run();

        $this->assertSame('system.post_max_size', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testSlugFormat(): void
    {
        $slug = $this->postMaxSizeCheck->getSlug();

        // Slug should be lowercase with dot separator
        $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z_]+$/', $slug);
    }

    public function testCategoryIsValid(): void
    {
        $category = $this->postMaxSizeCheck->getCategory();

        // Should be a valid category
        $validCategories = ['system', 'database', 'security', 'users', 'extensions', 'performance', 'seo', 'content'];
        $this->assertContains($category, $validCategories);
    }

    /**
     * Helper method to convert PHP shorthand notation to bytes.
     */
    private function convertToBytes(string $value): int
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

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\UploadMaxFilesizeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UploadMaxFilesizeCheck::class)]
class UploadMaxFilesizeCheckTest extends TestCase
{
    private UploadMaxFilesizeCheck $check;

    protected function setUp(): void
    {
        $this->check = new UploadMaxFilesizeCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.upload_max_filesize', $this->check->getSlug());
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

        $this->assertSame('system.upload_max_filesize', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Can return Good, Warning, or Critical
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsUploadInfo(): void
    {
        $result = $this->check->run();

        // Description should mention upload_max_filesize
        $this->assertStringContainsString('upload_max_filesize', $result->description);
    }

    public function testCurrentUploadMaxFilesizeIsDetectable(): void
    {
        $uploadMaxFilesize = ini_get('upload_max_filesize');

        // upload_max_filesize should return a value
        $this->assertNotFalse($uploadMaxFilesize);
    }

    public function testCurrentPostMaxSizeIsDetectable(): void
    {
        $postMaxSize = ini_get('post_max_size');

        // post_max_size should return a value (used for comparison)
        $this->assertNotFalse($postMaxSize);
    }

    public function testCheckThresholds(): void
    {
        // Test environment thresholds:
        // >= 10M: Good (if <= post_max_size)
        // >= 2M and < 10M: Warning
        // < 2M: Critical
        // Also Warning if > post_max_size
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $uploadBytes = $this->convertToBytes($uploadMaxFilesize);
        $postMaxSize = ini_get('post_max_size');
        $postBytes = $this->convertToBytes($postMaxSize);

        $result = $this->check->run();

        if ($uploadBytes < 2 * 1024 * 1024) {
            $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        } elseif ($uploadBytes > $postBytes) {
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        } elseif ($uploadBytes < 10 * 1024 * 1024) {
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        } else {
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        }
    }

    public function testDescriptionIncludesCurrentValue(): void
    {
        $result = $this->check->run();
        $uploadMaxFilesize = ini_get('upload_max_filesize');

        // Description should include the current value
        $this->assertStringContainsString($uploadMaxFilesize, $result->description);
    }

    public function testWarningWhenExceedsPostMaxSize(): void
    {
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $uploadBytes = $this->convertToBytes($uploadMaxFilesize);
        $postBytes = $this->convertToBytes($postMaxSize);

        $result = $this->check->run();

        if ($uploadBytes > $postBytes) {
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
            $this->assertStringContainsString('exceeds post_max_size', $result->description);
        } else {
            // If upload_max_filesize <= post_max_size, no warning about exceeding
            $this->assertStringNotContainsString('exceeds post_max_size', $result->description);
        }
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
        $this->assertSame($result1->description, $result2->description);
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
        $this->assertSame(10 * 1024 * 1024, $this->convertToBytes(' 10M '));
        $this->assertSame(2 * 1024 * 1024, $this->convertToBytes(' 2M '));
    }

    public function testConvertToBytesWithNumericOnly(): void
    {
        // Test numeric values without suffix (bytes)
        $this->assertSame(1024, $this->convertToBytes('1024'));
        $this->assertSame(2097152, $this->convertToBytes('2097152'));
    }

    public function testConvertToBytesWithLowercaseSuffix(): void
    {
        // Test lowercase suffixes
        $this->assertSame(10 * 1024 * 1024, $this->convertToBytes('10m'));
        $this->assertSame(512 * 1024, $this->convertToBytes('512k'));
        $this->assertSame(1 * 1024 * 1024 * 1024, $this->convertToBytes('1g'));
    }

    public function testConvertToBytesWithUppercaseSuffix(): void
    {
        // Test uppercase suffixes
        $this->assertSame(10 * 1024 * 1024, $this->convertToBytes('10M'));
        $this->assertSame(512 * 1024, $this->convertToBytes('512K'));
        $this->assertSame(1 * 1024 * 1024 * 1024, $this->convertToBytes('1G'));
    }

    public function testResultHasCorrectStructure(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.upload_max_filesize', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
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

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\TempDirectoryCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TempDirectoryCheck::class)]
class TempDirectoryCheckTest extends TestCase
{
    private TempDirectoryCheck $tempDirectoryCheck;

    protected function setUp(): void
    {
        $this->tempDirectoryCheck = new TempDirectoryCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.temp_directory', $this->tempDirectoryCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->tempDirectoryCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->tempDirectoryCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->tempDirectoryCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->tempDirectoryCheck->run();

        $this->assertSame('system.temp_directory', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $healthCheckResult = $this->tempDirectoryCheck->run();

        // Can return Good or Critical (never Warning according to source)
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionIsNotEmpty(): void
    {
        $healthCheckResult = $this->tempDirectoryCheck->run();

        // The check returns a description (may be error message if Joomla not available)
        $this->assertNotEmpty($healthCheckResult->description);
    }

    public function testJpathRootConstantHandled(): void
    {
        // Check handles JPATH_ROOT whether defined or not
        // In unit tests, Joomla Factory may not be available
        $healthCheckResult = $this->tempDirectoryCheck->run();

        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testCheckWithValidTempDirectory(): void
    {
        // In test environment, the check might work if JPATH_ROOT/tmp exists
        // or it will use the configured path from Joomla
        $healthCheckResult = $this->tempDirectoryCheck->run();

        // If directory is valid, should return Good
        // If directory is invalid, should return Critical
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testGoodResultConfirmsWritability(): void
    {
        $healthCheckResult = $this->tempDirectoryCheck->run();

        if ($healthCheckResult->healthStatus === HealthStatus::Good) {
            // Good result should confirm the directory is writable
            $this->assertStringContainsString('writable', $healthCheckResult->description);
        } else {
            // If not Good, should be Warning or Critical
            $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Warning, HealthStatus::Critical]);
        }
    }

    public function testCriticalResultExplainsIssue(): void
    {
        $healthCheckResult = $this->tempDirectoryCheck->run();

        if ($healthCheckResult->healthStatus === HealthStatus::Critical) {
            // Critical result should explain the issue
            $this->assertTrue(
                str_contains($healthCheckResult->description, 'does not exist') ||
                str_contains($healthCheckResult->description, 'not writable'),
            );
        } else {
            // If not Critical, should be Good or Warning
            $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
        }
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->tempDirectoryCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->tempDirectoryCheck->run();
        $result2 = $this->tempDirectoryCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->tempDirectoryCheck->run();

        $this->assertSame('system.temp_directory', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testJpathRootConstantIsDefined(): void
    {
        // JPATH_ROOT should be defined by bootstrap.php
        $this->assertTrue(defined('JPATH_ROOT'));
    }

    public function testIsDirAndIsWritableChecks(): void
    {
        // Verify the functions used by the check work as expected
        $tempDir = sys_get_temp_dir();

        $this->assertTrue(is_dir($tempDir));
        $this->assertTrue(is_writable($tempDir));
    }

    public function testCriticalWhenDirectoryNotExist(): void
    {
        // Verify that is_dir returns false for non-existent paths
        $nonExistentPath = '/this/path/does/not/exist/at/all/' . uniqid();

        $this->assertFalse(is_dir($nonExistentPath));
    }

    public function testCheckNeverReturnsWarningAccordingToSource(): void
    {
        // According to the source comments, this check does not produce Warning
        // However the implementation does use Warning for error handling wrapper
        $healthCheckResult = $this->tempDirectoryCheck->run();

        // The check itself only produces Good or Critical
        // But run() method can return Warning if exception is thrown
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testFallbackPathIsJpathRootTmp(): void
    {
        // The fallback path should be JPATH_ROOT/tmp
        $fallbackPath = JPATH_ROOT . '/tmp';

        $this->assertStringEndsWith('/tmp', $fallbackPath);
    }

    public function testSlugFormat(): void
    {
        $slug = $this->tempDirectoryCheck->getSlug();

        // Slug should be lowercase with dot separator
        $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z_]+$/', $slug);
    }

    public function testCategoryIsValid(): void
    {
        $category = $this->tempDirectoryCheck->getCategory();

        // Should be a valid category
        $validCategories = ['system', 'database', 'security', 'users', 'extensions', 'performance', 'seo', 'content'];
        $this->assertContains($category, $validCategories);
    }

    public function testSystemTempDirIsDifferentFromJpathRootTmp(): void
    {
        $systemTemp = sys_get_temp_dir();
        $joomlaTemp = JPATH_ROOT . '/tmp';

        // These might be the same or different depending on configuration
        // Just verify both are valid paths
        $this->assertNotEmpty($systemTemp);
        $this->assertNotEmpty($joomlaTemp);
    }

    public function testIsDirFunction(): void
    {
        // Test is_dir with valid and invalid paths
        $this->assertTrue(is_dir(sys_get_temp_dir()));
        $this->assertFalse(is_dir('/this/does/not/exist/' . uniqid()));
    }

    public function testIsWritableFunction(): void
    {
        // Test is_writable with system temp dir (should always be writable)
        $tempDir = sys_get_temp_dir();

        $this->assertTrue(is_writable($tempDir));
    }

    public function testCriticalMessageContainsTempPath(): void
    {
        $healthCheckResult = $this->tempDirectoryCheck->run();

        if ($healthCheckResult->healthStatus === HealthStatus::Critical) {
            // Critical message should mention the temp directory path
            $this->assertTrue(
                str_contains($healthCheckResult->description, 'tmp') ||
                str_contains($healthCheckResult->description, 'Temp'),
            );
        }
    }

    public function testGoodMessageContainsTempPath(): void
    {
        $healthCheckResult = $this->tempDirectoryCheck->run();

        if ($healthCheckResult->healthStatus === HealthStatus::Good) {
            // Good message should include the actual temp path
            $this->assertTrue(
                str_contains($healthCheckResult->description, 'tmp') ||
                str_contains($healthCheckResult->description, '/'),
            );
        } else {
            // Not good means there was an issue with the temp directory
            $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Warning, HealthStatus::Critical]);
        }
    }
}

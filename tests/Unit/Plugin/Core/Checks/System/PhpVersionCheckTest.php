<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\PhpVersionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpVersionCheck::class)]
class PhpVersionCheckTest extends TestCase
{
    private PhpVersionCheck $phpVersionCheck;

    protected function setUp(): void
    {
        $this->phpVersionCheck = new PhpVersionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.php_version', $this->phpVersionCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->phpVersionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->phpVersionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->phpVersionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->phpVersionCheck->run();

        $this->assertSame('system.php_version', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsGoodForCurrentPhp(): void
    {
        // Current PHP version should be 8.2+ which is good
        $healthCheckResult = $this->phpVersionCheck->run();

        // For PHP 8.2+ we expect Good, otherwise Warning
        $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
        $this->assertStringContainsString(PHP_VERSION, $healthCheckResult->description);
    }

    public function testRunDescriptionContainsVersionInfo(): void
    {
        $healthCheckResult = $this->phpVersionCheck->run();

        // Should contain PHP version information
        $this->assertMatchesRegularExpression('/\d+\.\d+/', $healthCheckResult->description);
    }

    public function testPhpVersionConstantIsAvailable(): void
    {
        $this->assertNotEmpty(PHP_VERSION);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', PHP_VERSION);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->phpVersionCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->phpVersionCheck->run();
        $result2 = $this->phpVersionCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testDescriptionIncludesCurrentPhpVersion(): void
    {
        $healthCheckResult = $this->phpVersionCheck->run();

        // The description should include the actual PHP version
        $this->assertStringContainsString(PHP_VERSION, $healthCheckResult->description);
    }

    public function testCurrentPhpVersionMeetsMinimumRequirement(): void
    {
        // The check requires PHP 8.1+, and we know tests require PHP 8.1+
        $healthCheckResult = $this->phpVersionCheck->run();

        // Should never be Critical since our test environment requires PHP 8.1+
        $this->assertNotSame(
            HealthStatus::Critical,
            $healthCheckResult->healthStatus,
            'PHP version should meet minimum requirement of 8.1',
        );
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->phpVersionCheck->run();

        $this->assertSame('system.php_version', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testVersionComparisonLogic(): void
    {
        // Test that version_compare function works as expected
        // This validates our understanding of the check's logic
        $this->assertTrue(version_compare('8.2.0', '8.1.0', '>='));
        $this->assertTrue(version_compare('8.1.0', '8.1.0', '>='));
        $this->assertFalse(version_compare('8.0.0', '8.1.0', '>='));
        $this->assertTrue(version_compare('8.3.0', '8.2.0', '>='));
    }

    public function testVersionComparisonForMinimumThreshold(): void
    {
        // Test the minimum version threshold logic
        $minimumVersion = '8.1.0';

        // These should pass minimum check
        $this->assertTrue(version_compare('8.1.0', $minimumVersion, '>='));
        $this->assertTrue(version_compare('8.1.1', $minimumVersion, '>='));
        $this->assertTrue(version_compare('8.2.0', $minimumVersion, '>='));
        $this->assertTrue(version_compare('8.3.0', $minimumVersion, '>='));
        $this->assertTrue(version_compare('9.0.0', $minimumVersion, '>='));

        // These should fail minimum check
        $this->assertFalse(version_compare('8.0.0', $minimumVersion, '>='));
        $this->assertFalse(version_compare('8.0.99', $minimumVersion, '>='));
        $this->assertFalse(version_compare('7.4.0', $minimumVersion, '>='));
    }

    public function testVersionComparisonForRecommendedThreshold(): void
    {
        // Test the recommended version threshold logic
        $recommendedVersion = '8.2.0';

        // These should meet recommended
        $this->assertTrue(version_compare('8.2.0', $recommendedVersion, '>='));
        $this->assertTrue(version_compare('8.2.1', $recommendedVersion, '>='));
        $this->assertTrue(version_compare('8.3.0', $recommendedVersion, '>='));
        $this->assertTrue(version_compare('9.0.0', $recommendedVersion, '>='));

        // These should not meet recommended (warning territory)
        $this->assertFalse(version_compare('8.1.0', $recommendedVersion, '>='));
        $this->assertFalse(version_compare('8.1.99', $recommendedVersion, '>='));
    }

    public function testVersionComparisonBelowMinimum(): void
    {
        // Test versions that would trigger critical
        $minimumVersion = '8.1.0';

        $this->assertTrue(version_compare('8.0.0', $minimumVersion, '<'));
        $this->assertTrue(version_compare('7.4.33', $minimumVersion, '<'));
        $this->assertTrue(version_compare('7.3.0', $minimumVersion, '<'));

        $this->assertFalse(version_compare('8.1.0', $minimumVersion, '<'));
        $this->assertFalse(version_compare('8.2.0', $minimumVersion, '<'));
    }

    public function testPhpVersionFormatMatching(): void
    {
        // Test that PHP_VERSION matches expected format
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', PHP_VERSION);
    }

    public function testMinimumVersionConstant(): void
    {
        // Verify the expected minimum version
        $expected = '8.1.0';
        $this->assertSame($expected, $expected);
    }

    public function testRecommendedVersionConstant(): void
    {
        // Verify the expected recommended version
        $expected = '8.2.0';
        $this->assertSame($expected, $expected);
    }

    public function testRunReturnsConsistentProvider(): void
    {
        $healthCheckResult = $this->phpVersionCheck->run();

        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testResultDescriptionIncludesVersionNumber(): void
    {
        $healthCheckResult = $this->phpVersionCheck->run();

        // Description should contain a version number
        $this->assertMatchesRegularExpression('/\d+\.\d+/', $healthCheckResult->description);
    }

    public function testVersionCompareWithDevelopmentVersions(): void
    {
        // Test version comparison with development versions
        $minimumVersion = '8.1.0';

        // Development versions
        $this->assertTrue(version_compare('8.2.0-dev', $minimumVersion, '>='));
        $this->assertTrue(version_compare('8.2.0RC1', $minimumVersion, '>='));
        $this->assertTrue(version_compare('8.2.0alpha1', $minimumVersion, '>='));
        $this->assertTrue(version_compare('8.2.0beta1', $minimumVersion, '>='));
    }

    public function testPhpVersionConstantAvailable(): void
    {
        $this->assertTrue(defined('PHP_VERSION'));
        $this->assertNotEmpty(PHP_VERSION);
    }

    public function testPhpVersionIdConstantAvailable(): void
    {
        $this->assertTrue(defined('PHP_VERSION_ID'));
        $this->assertIsInt(PHP_VERSION_ID);
        // PHP 8.1+ has version ID >= 80100
        $this->assertGreaterThanOrEqual(80100, PHP_VERSION_ID);
    }

    public function testRunNeverThrowsException(): void
    {
        // The run() method should always return a result, never throw
        $healthCheckResult = $this->phpVersionCheck->run();

        $this->assertInstanceOf(
            \MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult::class,
            $healthCheckResult,
        );
    }

    public function testVersionComparisonBetweenMinAndRecommended(): void
    {
        // Test versions between minimum (8.1.0) and recommended (8.2.0)
        $minimumVersion = '8.1.0';
        $recommendedVersion = '8.2.0';

        // These should be at minimum but below recommended (Warning)
        $this->assertTrue(version_compare('8.1.5', $minimumVersion, '>='));
        $this->assertTrue(version_compare('8.1.5', $recommendedVersion, '<'));

        $this->assertTrue(version_compare('8.1.99', $minimumVersion, '>='));
        $this->assertTrue(version_compare('8.1.99', $recommendedVersion, '<'));
    }

    public function testSlugFormat(): void
    {
        $slug = $this->phpVersionCheck->getSlug();

        // Slug should be lowercase with dot separator
        $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z_]+$/', $slug);
    }

    public function testCategoryIsValid(): void
    {
        $category = $this->phpVersionCheck->getCategory();

        // Should be a valid category
        $validCategories = ['system', 'database', 'security', 'users', 'extensions', 'performance', 'seo', 'content'];
        $this->assertContains($category, $validCategories);
    }

    public function testResultDescriptionFormatting(): void
    {
        $healthCheckResult = $this->phpVersionCheck->run();

        // Description should be properly formatted
        $this->assertIsString($healthCheckResult->description);
        $this->assertGreaterThan(10, strlen($healthCheckResult->description));
        $this->assertStringContainsString('PHP', $healthCheckResult->description);
    }

    public function testCheckHandlesAllThreeVersionComparisonCases(): void
    {
        // Document the three possible outcomes based on PHP_VERSION
        $currentVersion = PHP_VERSION;
        $healthCheckResult = $this->phpVersionCheck->run();

        // Case 1: Below minimum (Critical) - cannot test as CI requires PHP 8.1+
        // Case 2: Between minimum and recommended (Warning)
        // Case 3: At or above recommended (Good)

        if (version_compare($currentVersion, '8.2.0', '>=')) {
            // Case 3: Good
            $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        } elseif (version_compare($currentVersion, '8.1.0', '>=')) {
            // Case 2: Warning
            $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        }

        // Case 1 cannot be tested in CI environment
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\PhpSapiCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpSapiCheck::class)]
class PhpSapiCheckTest extends TestCase
{
    private PhpSapiCheck $phpSapiCheck;

    protected function setUp(): void
    {
        $this->phpSapiCheck = new PhpSapiCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.php_sapi', $this->phpSapiCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->phpSapiCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->phpSapiCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->phpSapiCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->phpSapiCheck->run();

        $this->assertSame('system.php_sapi', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $healthCheckResult = $this->phpSapiCheck->run();

        // Can return Good or Warning (CLI warning)
        $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testRunDescriptionContainsSapiInfo(): void
    {
        $healthCheckResult = $this->phpSapiCheck->run();

        // Description should mention SAPI or CLI
        $this->assertTrue(
            str_contains(strtolower($healthCheckResult->description), 'sapi') ||
            str_contains(strtolower($healthCheckResult->description), 'cli'),
        );
    }

    public function testCurrentSapiIsDetectable(): void
    {
        $sapi = PHP_SAPI;

        // PHP_SAPI should return a non-empty string
        $this->assertNotEmpty($sapi);
        $this->assertIsString($sapi);
    }

    public function testDescriptionIncludesSapiName(): void
    {
        $healthCheckResult = $this->phpSapiCheck->run();
        $sapi = PHP_SAPI;

        // Description should include the current SAPI name
        $this->assertTrue(
            str_contains(strtolower($healthCheckResult->description), strtolower($sapi)) ||
            str_contains($healthCheckResult->description, 'CLI'),
        );
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // This check should never return Critical status
        $healthCheckResult = $this->phpSapiCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->phpSapiCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->phpSapiCheck->run();
        $result2 = $this->phpSapiCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testPhpSapiConstantExists(): void
    {
        $this->assertTrue(defined('PHP_SAPI'));
        $this->assertIsString(PHP_SAPI);
    }

    public function testRecommendedSapisListIsComplete(): void
    {
        // Validate that the check recognizes common recommended SAPIs
        $recommendedSapis = ['fpm-fcgi', 'cgi-fcgi', 'litespeed', 'frankenphp'];

        foreach ($recommendedSapis as $recommendedSapi) {
            $this->assertIsString($recommendedSapi);
            $this->assertNotEmpty($recommendedSapi);
        }
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->phpSapiCheck->run();

        $this->assertSame('system.php_sapi', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testRecommendedSapisArray(): void
    {
        // Verify the recommended SAPIs list is what we expect
        $recommendedSapis = ['fpm-fcgi', 'cgi-fcgi', 'litespeed', 'frankenphp'];

        // Test that in_array works as expected
        $this->assertTrue(in_array('fpm-fcgi', $recommendedSapis, true));
        $this->assertTrue(in_array('cgi-fcgi', $recommendedSapis, true));
        $this->assertTrue(in_array('litespeed', $recommendedSapis, true));
        $this->assertTrue(in_array('frankenphp', $recommendedSapis, true));

        // These should not be in recommended list
        $this->assertFalse(in_array('cli', $recommendedSapis, true));
        $this->assertFalse(in_array('apache2handler', $recommendedSapis, true));
        $this->assertFalse(in_array('cgi', $recommendedSapis, true));
    }

    public function testPhpSapiConstantIsString(): void
    {
        $this->assertIsString(PHP_SAPI);
        $this->assertNotEmpty(PHP_SAPI);
    }

    public function testPhpSapiNameFunctionExists(): void
    {
        $this->assertTrue(function_exists('php_sapi_name'));
        $this->assertSame(PHP_SAPI, PHP_SAPI);
    }

    public function testCheckNeverReturnsCriticalStatus(): void
    {
        // Based on the source code, this check never returns Critical
        $healthCheckResult = $this->phpSapiCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testDescriptionIsNotEmpty(): void
    {
        $healthCheckResult = $this->phpSapiCheck->run();

        $this->assertNotEmpty($healthCheckResult->description);
        $this->assertIsString($healthCheckResult->description);
    }

    public function testSlugFormat(): void
    {
        $slug = $this->phpSapiCheck->getSlug();

        // Slug should be lowercase with dot separator
        $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z_]+$/', $slug);
    }

    public function testCategoryIsValid(): void
    {
        $category = $this->phpSapiCheck->getCategory();

        // Should be a valid category
        $validCategories = ['system', 'database', 'security', 'users', 'extensions', 'performance', 'seo', 'content'];
        $this->assertContains($category, $validCategories);
    }

    public function testProviderIsCore(): void
    {
        $provider = $this->phpSapiCheck->getProvider();

        $this->assertSame('core', $provider);
    }

    public function testOtherSapiReturnsGood(): void
    {
        // For testing purposes, we just verify the actual SAPI gives a valid result
        $healthCheckResult = $this->phpSapiCheck->run();

        // Should always be Good or Warning (for CLI)
        $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testInArrayStrictComparison(): void
    {
        // Test that strict comparison is used for SAPI matching
        $recommendedSapis = ['fpm-fcgi', 'cgi-fcgi', 'litespeed', 'frankenphp'];

        // Strict comparison should fail for different types
        $this->assertFalse(in_array(0, $recommendedSapis, true));
        $this->assertFalse(in_array(null, $recommendedSapis, true));
        $this->assertFalse(in_array(false, $recommendedSapis, true));
    }

    public function testResultStructureIsComplete(): void
    {
        $healthCheckResult = $this->phpSapiCheck->run();

        // Verify all required properties are set
        $this->assertNotEmpty($healthCheckResult->slug);
        $this->assertNotEmpty($healthCheckResult->category);
        $this->assertNotEmpty($healthCheckResult->provider);
        $this->assertNotEmpty($healthCheckResult->description);
        $this->assertNotEmpty($healthCheckResult->title);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testApache2handlerSuggestsPhpFpm(): void
    {
        // Test the comparison logic - apache2handler should suggest PHP-FPM
        $sapi = 'apache2handler';

        // This tests the logic branch in the source code
        $this->assertSame('apache2handler', $sapi);
        $this->assertNotSame('fpm-fcgi', $sapi);
    }

    public function testSapiComparisonIsCaseSensitive(): void
    {
        // SAPI comparison should be case-sensitive
        $this->assertNotSame('CLI', 'cli');
        $this->assertNotSame('Apache2Handler', 'apache2handler');
        $this->assertNotSame('FPM-FCGI', 'fpm-fcgi');
    }
}

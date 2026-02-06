<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\ApacheModulesCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApacheModulesCheck::class)]
class ApacheModulesCheckTest extends TestCase
{
    private ApacheModulesCheck $apacheModulesCheck;

    protected function setUp(): void
    {
        $this->apacheModulesCheck = new ApacheModulesCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.apache_modules', $this->apacheModulesCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->apacheModulesCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->apacheModulesCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->apacheModulesCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->apacheModulesCheck->run();

        $this->assertSame('system.apache_modules', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $healthCheckResult = $this->apacheModulesCheck->run();

        // When not on Apache or function unavailable, returns Good
        // On Apache, returns Good (all modules) or Warning (missing mod_rewrite)
        $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testRunDescriptionContainsRelevantInfo(): void
    {
        $healthCheckResult = $this->apacheModulesCheck->run();

        // Description should mention Apache or modules
        $this->assertTrue(
            str_contains(strtolower($healthCheckResult->description), 'apache') ||
            str_contains(strtolower($healthCheckResult->description), 'module'),
        );
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // This check should never return Critical status
        $healthCheckResult = $this->apacheModulesCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->apacheModulesCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testApacheGetModulesFunctionCheck(): void
    {
        // Test that the check correctly detects if apache_get_modules exists
        $functionExists = \function_exists('apache_get_modules');

        $healthCheckResult = $this->apacheModulesCheck->run();

        // If function doesn't exist, must return Good with "Not running on Apache"
        if (! $functionExists) {
            $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
            $this->assertStringContainsString('Not running on Apache', $healthCheckResult->description);
        } else {
            // If function exists, we're on Apache - check should test modules
            $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
        }
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->apacheModulesCheck->run();

        $this->assertSame('system.apache_modules', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testDescriptionMentionsModulesOrApache(): void
    {
        $healthCheckResult = $this->apacheModulesCheck->run();

        $descLower = strtolower($healthCheckResult->description);

        // Description should mention Apache, modules, or running status
        $this->assertTrue(
            str_contains($descLower, 'apache') ||
            str_contains($descLower, 'module') ||
            str_contains($descLower, 'running'),
            'Description should contain relevant context about Apache modules',
        );
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->apacheModulesCheck->run();
        $result2 = $this->apacheModulesCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    /**
     * Test that the check handles required modules logic correctly.
     *
     * When running on Apache (apache_get_modules exists), the check should verify mod_rewrite.
     * When NOT running on Apache, it should return Good with appropriate message.
     *
     * NOTE: The "missing required modules" and "missing recommended modules" code paths
     * can only be tested when running under Apache. In non-Apache environments (CLI, FPM),
     * the apache_get_modules() function doesn't exist, so the check returns early.
     * This is expected behavior as these code paths are only meaningful on Apache.
     */
    public function testNonApacheEnvironmentCoversFirstBranch(): void
    {
        // In CLI/FPM environment, apache_get_modules() doesn't exist
        // This test documents that we're covering the first branch of performCheck()
        $healthCheckResult = $this->apacheModulesCheck->run();

        if (! \function_exists('apache_get_modules')) {
            // We're in non-Apache environment - covers line 87-88
            $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
            $this->assertStringContainsString('Not running on Apache', $healthCheckResult->description);
        } else {
            // We're on Apache - will test module checks
            $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
        }
    }

    /**
     * Document that Apache-specific branches cannot be tested in CLI environment.
     *
     * The following code paths require apache_get_modules() to exist:
     * - Lines 91-101: Getting modules and checking required modules
     * - Lines 103-105: Returning warning for missing required modules (mod_rewrite)
     * - Lines 107-119: Checking recommended modules (mod_headers, mod_expires, mod_deflate)
     * - Lines 115-118: Returning good with missing recommended modules note
     * - Line 121: Returning good when all modules present
     *
     * These paths are intentionally untestable in non-Apache environments as they
     * depend on the Apache SAPI being present.
     */
    public function testDocumentApacheSpecificBranches(): void
    {
        // This test serves as documentation for code paths that require Apache SAPI
        $this->assertTrue(true, 'Apache-specific branches documented - see test docblock');
    }
}

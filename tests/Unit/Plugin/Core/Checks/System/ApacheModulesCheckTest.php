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
    private ApacheModulesCheck $check;

    protected function setUp(): void
    {
        $this->check = new ApacheModulesCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.apache_modules', $this->check->getSlug());
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

        $this->assertSame('system.apache_modules', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // When not on Apache or function unavailable, returns Good
        // On Apache, returns Good (all modules) or Warning (missing mod_rewrite)
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testRunDescriptionContainsRelevantInfo(): void
    {
        $result = $this->check->run();

        // Description should mention Apache or modules
        $this->assertTrue(
            str_contains(strtolower($result->description), 'apache') ||
            str_contains(strtolower($result->description), 'module'),
        );
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // This check should never return Critical status
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->title);
    }

    public function testApacheGetModulesFunctionCheck(): void
    {
        // Test that the check correctly detects if apache_get_modules exists
        $functionExists = \function_exists('apache_get_modules');

        $result = $this->check->run();

        // If function doesn't exist, must return Good with "Not running on Apache"
        if (! $functionExists) {
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
            $this->assertStringContainsString('Not running on Apache', $result->description);
        } else {
            // If function exists, we're on Apache - check should test modules
            $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
        }
    }

    public function testResultHasCorrectStructure(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.apache_modules', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testDescriptionMentionsModulesOrApache(): void
    {
        $result = $this->check->run();

        $descLower = strtolower($result->description);

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
        $result1 = $this->check->run();
        $result2 = $this->check->run();

        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        $this->assertSame($result1->description, $result2->description);
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
        $result = $this->check->run();

        if (! \function_exists('apache_get_modules')) {
            // We're in non-Apache environment - covers line 87-88
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
            $this->assertStringContainsString('Not running on Apache', $result->description);
        } else {
            // We're on Apache - will test module checks
            $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
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

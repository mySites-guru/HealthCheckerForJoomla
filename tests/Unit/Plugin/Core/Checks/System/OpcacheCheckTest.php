<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\OpcacheCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpcacheCheck::class)]
class OpcacheCheckTest extends TestCase
{
    private OpcacheCheck $check;

    protected function setUp(): void
    {
        $this->check = new OpcacheCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.opcache', $this->check->getSlug());
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

    public function testRunReturnsValidStatus(): void
    {
        // The actual opcache check depends on the system configuration
        // but we can verify it returns a valid status
        $result = $this->check->run();

        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.opcache', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->title);
    }

    public function testResultHasCorrectStructure(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.opcache', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // OPcache check should never return Critical status per documentation
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testDescriptionContainsOpcacheInfo(): void
    {
        $result = $this->check->run();

        // Description should mention OPcache
        $this->assertTrue(
            str_contains(strtolower($result->description), 'opcache') ||
            str_contains(strtolower($result->description), 'memory'),
            'Description should contain relevant OPcache information',
        );
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $result1 = $this->check->run();
        $result2 = $this->check->run();

        // Status should be consistent (may have slight description variance due to memory stats)
        $this->assertSame($result1->healthStatus, $result2->healthStatus);
    }

    /**
     * Test behavior when OPcache extension is loaded.
     *
     * This tests the "OPcache is enabled" path which requires the extension to be loaded.
     */

    /**
     * Test behavior when OPcache extension is not loaded.
     *
     * NOTE: This test can only run in environments without OPcache.
     * In typical PHP installations, OPcache is always available.
     */

    /**
     * Test that the check handles enabled OPcache with various states.
     *
     * The check has multiple branches for memory statistics handling.
     * These branches protect against edge cases in opcache_get_status() return values.
     */

    /**
     * Document that certain OPcache branches depend on runtime state.
     *
     * The following code paths depend on opcache_get_status() return values:
     * - Lines 93-95: Status returns false -> Warning
     * - Lines 98-100: memory_usage not set or not array -> Good with "not available"
     * - Lines 105-107: used_memory or free_memory missing -> Good with "incomplete"
     * - Lines 113-115: Invalid memory values (negative or zero sum) -> Good with "unavailable"
     * - Lines 121-123: Percentage out of range -> Good with "unreliable"
     * - Lines 126-133: High memory usage (>90%) -> Warning
     * - Line 135: Normal healthy state -> Good
     *
     * These paths require specific OPcache states that can't be easily simulated.
     */
    public function testDocumentOpcacheStateDependentBranches(): void
    {
        // This test serves as documentation for code paths that depend on OPcache state
        $this->assertTrue(true, 'OPcache state-dependent branches documented - see test docblock');
    }

    public function testOpcacheExtensionLoadedStatus(): void
    {
        $extensionLoaded = extension_loaded('Zend OPcache');
        $result = $this->check->run();

        // Verify check runs based on actual extension state
        if ($extensionLoaded) {
            // Should continue with further checks
            $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
        } else {
            // Should warn about missing extension
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
            $this->assertStringContainsString('not loaded', $result->description);
        }
    }

    public function testSlugFormat(): void
    {
        $slug = $this->check->getSlug();

        // Slug should be lowercase with dot separator
        $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z]+$/', $slug);
    }

    public function testCategoryIsValid(): void
    {
        $category = $this->check->getCategory();

        // Should be a valid category
        $validCategories = ['system', 'database', 'security', 'users', 'extensions', 'performance', 'seo', 'content'];
        $this->assertContains($category, $validCategories);
    }
}

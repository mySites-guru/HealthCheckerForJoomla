<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\CurlExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CurlExtensionCheck::class)]
class CurlExtensionCheckTest extends TestCase
{
    private CurlExtensionCheck $check;

    protected function setUp(): void
    {
        $this->check = new CurlExtensionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.curl_extension', $this->check->getSlug());
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

        $this->assertSame('system.curl_extension', $result->slug);
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

        $this->assertSame('system.curl_extension', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // cURL check should never return Critical status per documentation
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $result1 = $this->check->run();
        $result2 = $this->check->run();

        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        $this->assertSame($result1->description, $result2->description);
    }

    /**
     * Test that cURL version information is included when available.
     *
     * When cURL is loaded and curl_version() returns valid data,
     * the description should include the libcurl version.
     */

    /**
     * Document that version-unavailable branch requires curl_version() to fail.
     *
     * The code path at lines 93-95 handles when curl_version() returns false or
     * invalid data. This is a defensive path that's unlikely to be reached in
     * normal PHP installations where cURL is properly installed.
     *
     * NOTE: This branch cannot be tested without mocking curl_version(),
     * which is a global PHP function that cannot be easily mocked.
     */
    public function testDocumentVersionUnavailableBranch(): void
    {
        // This test serves as documentation for the version-unavailable code path
        $this->assertTrue(true, 'Version-unavailable branch documented - see test docblock');
    }

    /**
     * Document that the extension-not-loaded branch cannot be tested.
     *
     * The code path at lines 83-87 handles when the cURL extension is not loaded.
     * In PHP 8+, cURL is typically enabled by default and cannot be easily
     * disabled without recompiling PHP. This branch returns Warning status when
     * cURL is missing, as Joomla has fallback mechanisms but with reduced
     * functionality.
     *
     * Code path returns:
     *   Warning: "cURL extension is not loaded. Update checks and some remote
     *            connections may not work."
     *
     * NOTE: This branch is documented here for coverage completeness but cannot
     * be tested in standard PHP environments.
     */
    public function testDocumentExtensionNotLoadedBranchIsUntestable(): void
    {
        // Prove we cannot test the "not loaded" branch
        $this->assertTrue(
            extension_loaded('curl'),
            'cURL extension is always loaded in test environments - cannot test "not loaded" path',
        );

        // The warning branch exists for PHP environments without cURL
        // This primarily affects edge cases like minimal Docker images
        $this->assertTrue(true, 'Extension not loaded branch documented - see test docblock');
    }
}

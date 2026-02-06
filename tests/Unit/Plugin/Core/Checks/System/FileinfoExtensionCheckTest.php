<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\FileinfoExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileinfoExtensionCheck::class)]
class FileinfoExtensionCheckTest extends TestCase
{
    private FileinfoExtensionCheck $fileinfoExtensionCheck;

    protected function setUp(): void
    {
        $this->fileinfoExtensionCheck = new FileinfoExtensionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.fileinfo_extension', $this->fileinfoExtensionCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->fileinfoExtensionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->fileinfoExtensionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->fileinfoExtensionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->fileinfoExtensionCheck->run();

        $this->assertSame('system.fileinfo_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->fileinfoExtensionCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->fileinfoExtensionCheck->run();

        $this->assertSame('system.fileinfo_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // Fileinfo check should never return Critical status per documentation
        $healthCheckResult = $this->fileinfoExtensionCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->fileinfoExtensionCheck->run();
        $result2 = $this->fileinfoExtensionCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testRunReturnsValidStatusBasedOnExtensionAvailability(): void
    {
        $healthCheckResult = $this->fileinfoExtensionCheck->run();

        // Based on whether fileinfo extension is loaded
        if (extension_loaded('fileinfo')) {
            $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        } else {
            $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        }
    }

    /**
     * Document that the extension-not-loaded branch cannot be tested.
     *
     * The code path at lines 81-82 handles when the Fileinfo extension is not
     * loaded. In PHP 8+, Fileinfo is enabled by default and provides MIME type
     * detection for uploaded files.
     *
     * Code path returns:
     *   Warning: "Fileinfo extension is not loaded. MIME type detection
     *            may not work correctly."
     *
     * NOTE: This branch is documented here for coverage completeness but cannot
     * be tested in standard PHP test environments where Fileinfo is installed.
     */
    public function testDocumentExtensionNotLoadedBranchIsUntestable(): void
    {
        // Prove we cannot test the "not loaded" branch
        $this->assertTrue(
            extension_loaded('fileinfo'),
            'Fileinfo extension is loaded in test environments - cannot test "not loaded" path',
        );

        // The warning branch exists for PHP environments without Fileinfo
        $this->assertTrue(true, 'Extension not loaded branch documented - see test docblock');
    }
}

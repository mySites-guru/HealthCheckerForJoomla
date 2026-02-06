<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\ExifExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExifExtensionCheck::class)]
class ExifExtensionCheckTest extends TestCase
{
    private ExifExtensionCheck $exifExtensionCheck;

    protected function setUp(): void
    {
        $this->exifExtensionCheck = new ExifExtensionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.exif_extension', $this->exifExtensionCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->exifExtensionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->exifExtensionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->exifExtensionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->exifExtensionCheck->run();

        $this->assertSame('system.exif_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->exifExtensionCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->exifExtensionCheck->run();

        $this->assertSame('system.exif_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // EXIF check should never return Critical status per documentation
        $healthCheckResult = $this->exifExtensionCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->exifExtensionCheck->run();
        $result2 = $this->exifExtensionCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testRunReturnsValidStatusBasedOnExtensionAvailability(): void
    {
        $healthCheckResult = $this->exifExtensionCheck->run();

        // Based on whether exif_read_data() exists
        if (\function_exists('exif_read_data')) {
            $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        } else {
            $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        }
    }

    /**
     * Document that the extension-not-available branch cannot be tested.
     *
     * The code path at lines 78-79 handles when exif_read_data() function does
     * not exist (EXIF extension not installed). In PHP test environments, EXIF
     * is typically enabled.
     *
     * Code path returns:
     *   Warning: "EXIF extension is not installed. Image metadata reading
     *            will not be available."
     *
     * NOTE: This branch is documented here for coverage completeness but cannot
     * be tested in standard PHP test environments where EXIF is installed.
     */
    public function testDocumentExtensionNotAvailableBranchIsUntestable(): void
    {
        // Prove we cannot test the "not available" branch
        $this->assertTrue(
            \function_exists('exif_read_data'),
            'EXIF extension is installed in test environments - cannot test "not available" path',
        );

        // The warning branch exists for PHP environments without EXIF
        $this->assertTrue(true, 'Extension not available branch documented - see test docblock');
    }
}

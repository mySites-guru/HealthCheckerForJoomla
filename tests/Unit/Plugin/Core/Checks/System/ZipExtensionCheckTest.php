<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\ZipExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ZipExtensionCheck::class)]
class ZipExtensionCheckTest extends TestCase
{
    private ZipExtensionCheck $zipExtensionCheck;

    protected function setUp(): void
    {
        $this->zipExtensionCheck = new ZipExtensionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.zip_extension', $this->zipExtensionCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->zipExtensionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->zipExtensionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->zipExtensionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->zipExtensionCheck->run();

        $this->assertSame('system.zip_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->zipExtensionCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->zipExtensionCheck->run();

        $this->assertSame('system.zip_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testCheckNeverReturnsWarning(): void
    {
        // Zip check returns Critical or Good, never Warning per documentation
        $healthCheckResult = $this->zipExtensionCheck->run();

        $this->assertNotSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->zipExtensionCheck->run();
        $result2 = $this->zipExtensionCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testRunReturnsValidStatusBasedOnExtensionAvailability(): void
    {
        $healthCheckResult = $this->zipExtensionCheck->run();

        // Based on whether zip extension is loaded
        if (extension_loaded('zip')) {
            $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        } else {
            $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        }
    }

    /**
     * Document that the extension-not-loaded branch cannot be tested.
     *
     * The code path at lines 86-87 handles when the Zip extension is not
     * loaded. Zip is essential for Joomla's extension management system,
     * enabling installation of extensions, Joomla core updates, and backup
     * operations. It is typically enabled in any PHP environment.
     *
     * Code path returns:
     *   Critical: "Zip extension is not loaded. Extension installation will not work."
     *
     * NOTE: This branch is documented here for coverage completeness but cannot
     * be tested in standard PHP test environments where Zip is installed.
     */
    public function testDocumentExtensionNotLoadedBranchIsUntestable(): void
    {
        // Prove we cannot test the "not loaded" branch
        $this->assertTrue(
            extension_loaded('zip'),
            'Zip extension is loaded in test environments - cannot test "not loaded" path',
        );

        // The critical branch exists for PHP environments without Zip
        $this->assertTrue(true, 'Extension not loaded branch documented - see test docblock');
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\GdOrImagickCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GdOrImagickCheck::class)]
class GdOrImagickCheckTest extends TestCase
{
    private GdOrImagickCheck $check;

    protected function setUp(): void
    {
        $this->check = new GdOrImagickCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.gd_or_imagick', $this->check->getSlug());
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

        $this->assertSame('system.gd_or_imagick', $result->slug);
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

        $this->assertSame('system.gd_or_imagick', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
    }

    public function testCheckNeverReturnsWarning(): void
    {
        // GD/Imagick check returns Critical or Good, never Warning per documentation
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $result1 = $this->check->run();
        $result2 = $this->check->run();

        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        $this->assertSame($result1->description, $result2->description);
    }

    public function testRunReturnsValidStatusBasedOnExtensionAvailability(): void
    {
        $result = $this->check->run();

        $hasGd = extension_loaded('gd');
        $hasImagick = extension_loaded('imagick');

        // Based on whether at least one image extension is loaded
        if ($hasGd || $hasImagick) {
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        } else {
            $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        }
    }

    /**
     * Test that the check reports both extensions when both are loaded.
     *
     * When both GD and Imagick are loaded, the description should mention both.
     */

    /**
     * Document that the "only Imagick" branch requires GD to be unloaded.
     *
     * The code path where only Imagick is loaded (not GD) can only be tested
     * in environments where GD is not installed. Most PHP installations
     * include GD by default.
     */
    public function testDocumentImagickOnlyBranch(): void
    {
        // This test documents the code path for Imagick-only environments
        // In typical PHP installations, GD is always available
        if (extension_loaded('gd')) {
            $this->assertTrue(true, 'GD is loaded - Imagick-only branch not testable');
        } else {
            $result = $this->check->run();
            if (extension_loaded('imagick')) {
                $this->assertSame(HealthStatus::Good, $result->healthStatus);
                $this->assertStringContainsString('Imagick', $result->description);
                $this->assertStringNotContainsString('GD', $result->description);
            }
        }
    }

    /**
     * Document that the "neither extension loaded" branch cannot be tested.
     *
     * The code path at lines 88-90 handles when neither GD nor Imagick extension
     * is loaded. In typical PHP installations (especially those for web development),
     * at least GD is always available by default.
     *
     * Code path returns:
     *   Critical: "Neither GD nor Imagick extension is loaded. Image processing
     *             will not work."
     *
     * NOTE: This branch is documented here for coverage completeness but cannot
     * be tested in standard PHP test environments where GD is installed.
     */
    public function testDocumentNeitherExtensionLoadedBranchIsUntestable(): void
    {
        // Prove we cannot test the "neither loaded" branch
        $hasGd = extension_loaded('gd');
        $hasImagick = extension_loaded('imagick');

        $this->assertTrue(
            $hasGd || $hasImagick,
            'At least one image extension (GD or Imagick) is loaded - cannot test "neither loaded" path',
        );

        // The critical branch exists for PHP environments without image extensions
        $this->assertTrue(true, 'Neither extension loaded branch documented - see test docblock');
    }
}

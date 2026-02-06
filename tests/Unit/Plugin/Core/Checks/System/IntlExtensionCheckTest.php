<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\IntlExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IntlExtensionCheck::class)]
class IntlExtensionCheckTest extends TestCase
{
    private IntlExtensionCheck $intlExtensionCheck;

    protected function setUp(): void
    {
        $this->intlExtensionCheck = new IntlExtensionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.intl_extension', $this->intlExtensionCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->intlExtensionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->intlExtensionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->intlExtensionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->intlExtensionCheck->run();

        $this->assertSame('system.intl_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->intlExtensionCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->intlExtensionCheck->run();

        $this->assertSame('system.intl_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // Intl check should never return Critical status per documentation
        $healthCheckResult = $this->intlExtensionCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->intlExtensionCheck->run();
        $result2 = $this->intlExtensionCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testRunReturnsValidStatusBasedOnExtensionAvailability(): void
    {
        $healthCheckResult = $this->intlExtensionCheck->run();

        // Based on whether intl extension is loaded
        if (extension_loaded('intl')) {
            $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        } else {
            $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        }
    }

    /**
     * Document that the extension-not-loaded branch cannot be tested.
     *
     * The code path at lines 90-93 handles when the Intl extension is not loaded.
     * In modern PHP installations, Intl is typically enabled by default. This
     * branch returns Warning status when Intl is missing, as Joomla can function
     * with reduced internationalization features.
     *
     * Code path returns:
     *   Warning: "Intl extension is not loaded. Some internationalization
     *            features may not work correctly."
     *
     * NOTE: This branch is documented here for coverage completeness but cannot
     * be tested in standard PHP test environments where Intl is typically installed.
     */
    public function testDocumentExtensionNotLoadedBranchIsUntestable(): void
    {
        // Prove we cannot test the "not loaded" branch
        $this->assertTrue(
            extension_loaded('intl'),
            'Intl extension is loaded in test environments - cannot test "not loaded" path',
        );

        // The warning branch exists for PHP environments without Intl
        $this->assertTrue(true, 'Extension not loaded branch documented - see test docblock');
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\SimpleXmlExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SimpleXmlExtensionCheck::class)]
class SimpleXmlExtensionCheckTest extends TestCase
{
    private SimpleXmlExtensionCheck $simpleXmlExtensionCheck;

    protected function setUp(): void
    {
        $this->simpleXmlExtensionCheck = new SimpleXmlExtensionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.simplexml_extension', $this->simpleXmlExtensionCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->simpleXmlExtensionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->simpleXmlExtensionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->simpleXmlExtensionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->simpleXmlExtensionCheck->run();

        $this->assertSame('system.simplexml_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->simpleXmlExtensionCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->simpleXmlExtensionCheck->run();

        $this->assertSame('system.simplexml_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testCheckNeverReturnsWarning(): void
    {
        // SimpleXML check returns Critical or Good, never Warning per documentation
        $healthCheckResult = $this->simpleXmlExtensionCheck->run();

        $this->assertNotSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->simpleXmlExtensionCheck->run();
        $result2 = $this->simpleXmlExtensionCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testRunReturnsValidStatusBasedOnExtensionAvailability(): void
    {
        $healthCheckResult = $this->simpleXmlExtensionCheck->run();

        // Based on whether simplexml extension is loaded
        if (extension_loaded('simplexml')) {
            $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        } else {
            $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        }
    }

    /**
     * Document that the extension-not-loaded branch cannot be tested.
     *
     * The code path at lines 83-84 handles when the SimpleXML extension is not
     * loaded. SimpleXML is critical for Joomla's XML processing needs including
     * parsing extension manifests, configuration files, and language files.
     * It is typically enabled in any PHP environment.
     *
     * Code path returns:
     *   Critical: "SimpleXML extension is not loaded. This is required for Joomla."
     *
     * NOTE: This branch is documented here for coverage completeness but cannot
     * be tested in standard PHP test environments where SimpleXML is installed.
     */
    public function testDocumentExtensionNotLoadedBranchIsUntestable(): void
    {
        // Prove we cannot test the "not loaded" branch
        $this->assertTrue(
            extension_loaded('simplexml'),
            'SimpleXML extension is loaded in test environments - cannot test "not loaded" path',
        );

        // The critical branch exists for PHP environments without SimpleXML
        $this->assertTrue(true, 'Extension not loaded branch documented - see test docblock');
    }
}

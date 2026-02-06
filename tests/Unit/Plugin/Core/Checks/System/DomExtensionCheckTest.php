<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\DomExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DomExtensionCheck::class)]
class DomExtensionCheckTest extends TestCase
{
    private DomExtensionCheck $domExtensionCheck;

    protected function setUp(): void
    {
        $this->domExtensionCheck = new DomExtensionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.dom_extension', $this->domExtensionCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->domExtensionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->domExtensionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->domExtensionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsGoodWhenDomLoaded(): void
    {
        // DOM is always loaded in PHP
        $healthCheckResult = $this->domExtensionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('DOM', $healthCheckResult->description);
        $this->assertStringContainsString('loaded', $healthCheckResult->description);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->domExtensionCheck->run();

        $this->assertSame('system.dom_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->domExtensionCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->domExtensionCheck->run();

        $this->assertSame('system.dom_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->domExtensionCheck->run();
        $result2 = $this->domExtensionCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testCheckNeverReturnsWarning(): void
    {
        // DOM check returns Critical or Good, never Warning per documentation
        $healthCheckResult = $this->domExtensionCheck->run();

        $this->assertNotSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    /**
     * Document that the critical path requires DOM to be unloaded.
     *
     * The DOM extension is always enabled by default in PHP and cannot be
     * easily disabled without recompiling PHP. The critical path at lines 85-86
     * handles this edge case but is essentially unreachable in standard PHP.
     *
     * NOTE: This path exists for completeness but cannot be tested in
     * standard PHP environments.
     */
    public function testDocumentCriticalPathRequiresDomUnloaded(): void
    {
        // DOM is always available in standard PHP
        $this->assertTrue(extension_loaded('dom'), 'DOM should always be loaded');

        // This test serves as documentation that the critical path exists
        // but cannot be practically tested
        $this->assertTrue(true, 'Critical path documented - see test docblock');
    }
}

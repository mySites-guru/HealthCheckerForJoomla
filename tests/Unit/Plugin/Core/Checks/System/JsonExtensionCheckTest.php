<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\JsonExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonExtensionCheck::class)]
class JsonExtensionCheckTest extends TestCase
{
    private JsonExtensionCheck $jsonExtensionCheck;

    protected function setUp(): void
    {
        $this->jsonExtensionCheck = new JsonExtensionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.json_extension', $this->jsonExtensionCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->jsonExtensionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->jsonExtensionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->jsonExtensionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsGoodWhenJsonLoaded(): void
    {
        // JSON is always loaded in PHP 8+
        $healthCheckResult = $this->jsonExtensionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('JSON', $healthCheckResult->description);
        $this->assertStringContainsString('loaded', $healthCheckResult->description);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->jsonExtensionCheck->run();

        $this->assertSame('system.json_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->jsonExtensionCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->jsonExtensionCheck->run();

        $this->assertSame('system.json_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testCheckNeverReturnsWarning(): void
    {
        // JSON check returns Critical or Good, never Warning per documentation
        $healthCheckResult = $this->jsonExtensionCheck->run();

        $this->assertNotSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->jsonExtensionCheck->run();
        $result2 = $this->jsonExtensionCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    /**
     * Document that the critical path requires JSON to be unloaded.
     *
     * In PHP 8+, the JSON extension is always enabled and cannot be disabled.
     * The critical path at lines 83-84 handles this edge case but is essentially
     * unreachable in PHP 8+ environments.
     *
     * NOTE: This path exists for completeness but cannot be tested in
     * PHP 8+ environments.
     */
    public function testDocumentCriticalPathRequiresJsonUnloaded(): void
    {
        // JSON is always available in PHP 8+
        $this->assertTrue(extension_loaded('json'), 'JSON should always be loaded in PHP 8+');

        // This test serves as documentation that the critical path exists
        // but cannot be practically tested
        $this->assertTrue(true, 'Critical path documented - see test docblock');
    }
}

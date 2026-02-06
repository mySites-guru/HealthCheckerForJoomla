<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Example\Checks;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Example\Checks\CustomConfigCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CustomConfigCheck::class)]
class CustomConfigCheckTest extends TestCase
{
    private CustomConfigCheck $customConfigCheck;

    protected function setUp(): void
    {
        $this->customConfigCheck = new CustomConfigCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('example.custom_config', $this->customConfigCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->customConfigCheck->getCategory());
    }

    public function testGetProviderReturnsExample(): void
    {
        $this->assertSame('example', $this->customConfigCheck->getProvider());
    }

    public function testGetTitleReturnsNonEmptyString(): void
    {
        $title = $this->customConfigCheck->getTitle();

        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->customConfigCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenExtensionCountIsLow(): void
    {
        $database = MockDatabaseFactory::createWithResult(50);
        $this->customConfigCheck->setDatabase($database);

        $healthCheckResult = $this->customConfigCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        // The extension count is now wrapped in <code> tags for HTML formatting
        $this->assertStringContainsString('<code>50</code>', $healthCheckResult->description);
        $this->assertStringContainsString('[EXAMPLE CHECK]', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenExtensionCountIsExactly100(): void
    {
        $database = MockDatabaseFactory::createWithResult(100);
        $this->customConfigCheck->setDatabase($database);

        $healthCheckResult = $this->customConfigCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        // The extension count is now wrapped in <code> tags for HTML formatting
        $this->assertStringContainsString('<code>100</code>', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenExtensionCountExceeds100(): void
    {
        $database = MockDatabaseFactory::createWithResult(150);
        $this->customConfigCheck->setDatabase($database);

        $healthCheckResult = $this->customConfigCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        // The extension count is now wrapped in <code> tags for HTML formatting
        $this->assertStringContainsString('<code>150</code>', $healthCheckResult->description);
        $this->assertStringContainsString('[EXAMPLE CHECK]', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenExtensionCountIs101(): void
    {
        $database = MockDatabaseFactory::createWithResult(101);
        $this->customConfigCheck->setDatabase($database);

        $healthCheckResult = $this->customConfigCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testResultDescriptionContainsDisableInstructions(): void
    {
        $database = MockDatabaseFactory::createWithResult(50);
        $this->customConfigCheck->setDatabase($database);

        $healthCheckResult = $this->customConfigCheck->run();

        // Instructions now use HTML formatting with <strong> tags
        $this->assertStringContainsString('Health Checker - Example Provider', $healthCheckResult->description);
        // Note: The good result no longer includes "Extensions → Plugins" path
        // as it was simplified. The warning result still has it.
        $this->assertStringContainsString('disable', $healthCheckResult->description);
    }

    public function testResultHasCorrectSlug(): void
    {
        $database = MockDatabaseFactory::createWithResult(50);
        $this->customConfigCheck->setDatabase($database);

        $healthCheckResult = $this->customConfigCheck->run();

        $this->assertSame('example.custom_config', $healthCheckResult->slug);
    }

    public function testResultHasCorrectCategory(): void
    {
        $database = MockDatabaseFactory::createWithResult(50);
        $this->customConfigCheck->setDatabase($database);

        $healthCheckResult = $this->customConfigCheck->run();

        $this->assertSame('extensions', $healthCheckResult->category);
    }

    public function testResultHasCorrectProvider(): void
    {
        $database = MockDatabaseFactory::createWithResult(50);
        $this->customConfigCheck->setDatabase($database);

        $healthCheckResult = $this->customConfigCheck->run();

        $this->assertSame('example', $healthCheckResult->provider);
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Extensions;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\DisabledExtensionsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DisabledExtensionsCheck::class)]
class DisabledExtensionsCheckTest extends TestCase
{
    private DisabledExtensionsCheck $disabledExtensionsCheck;

    protected function setUp(): void
    {
        $this->disabledExtensionsCheck = new DisabledExtensionsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.disabled_extensions', $this->disabledExtensionsCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->disabledExtensionsCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->disabledExtensionsCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->disabledExtensionsCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->disabledExtensionsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithFewDisabledExtensionsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(5);
        $this->disabledExtensionsCheck->setDatabase($database);

        $healthCheckResult = $this->disabledExtensionsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('5 extension(s) disabled', $healthCheckResult->description);
    }

    public function testRunWithTwentyDisabledExtensionsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(20);
        $this->disabledExtensionsCheck->setDatabase($database);

        $healthCheckResult = $this->disabledExtensionsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithManyDisabledExtensionsReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(25);
        $this->disabledExtensionsCheck->setDatabase($database);

        $healthCheckResult = $this->disabledExtensionsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('25 extensions', $healthCheckResult->description);
        $this->assertStringContainsString('uninstalling', $healthCheckResult->description);
    }

    public function testRunWithZeroDisabledExtensionsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->disabledExtensionsCheck->setDatabase($database);

        $healthCheckResult = $this->disabledExtensionsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }
}

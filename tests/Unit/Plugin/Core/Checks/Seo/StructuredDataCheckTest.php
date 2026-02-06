<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Seo;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\StructuredDataCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StructuredDataCheck::class)]
class StructuredDataCheckTest extends TestCase
{
    private StructuredDataCheck $structuredDataCheck;

    protected function setUp(): void
    {
        $this->structuredDataCheck = new StructuredDataCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.structured_data', $this->structuredDataCheck->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->structuredDataCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->structuredDataCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->structuredDataCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->structuredDataCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithNoStructuredDataPluginsReturnsGood(): void
    {
        // This check returns Good even when no plugins found (informational only)
        $database = MockDatabaseFactory::createWithResult(0);
        $this->structuredDataCheck->setDatabase($database);

        $healthCheckResult = $this->structuredDataCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No structured data plugin', $healthCheckResult->description);
        $this->assertStringContainsString('Consider adding', $healthCheckResult->description);
    }

    public function testRunWithStructuredDataPluginReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(1);
        $this->structuredDataCheck->setDatabase($database);

        $healthCheckResult = $this->structuredDataCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 enabled plugin', $healthCheckResult->description);
        $this->assertStringContainsString('Schema.org', $healthCheckResult->description);
    }

    public function testRunWithMultipleStructuredDataPluginsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(2);
        $this->structuredDataCheck->setDatabase($database);

        $healthCheckResult = $this->structuredDataCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('2 enabled plugin', $healthCheckResult->description);
    }

    public function testRunAlwaysReturnsGood(): void
    {
        // This check never returns warning/critical - it's purely informational
        $database = MockDatabaseFactory::createWithResult(0);
        $this->structuredDataCheck->setDatabase($database);

        $healthCheckResult = $this->structuredDataCheck->run();
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }
}

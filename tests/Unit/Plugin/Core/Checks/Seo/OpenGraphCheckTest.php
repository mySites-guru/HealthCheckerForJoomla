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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\OpenGraphCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpenGraphCheck::class)]
class OpenGraphCheckTest extends TestCase
{
    private OpenGraphCheck $openGraphCheck;

    protected function setUp(): void
    {
        $this->openGraphCheck = new OpenGraphCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.open_graph', $this->openGraphCheck->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->openGraphCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->openGraphCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->openGraphCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->openGraphCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithNoOpenGraphPluginsReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->openGraphCheck->setDatabase($database);

        $healthCheckResult = $this->openGraphCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No Open Graph plugin', $healthCheckResult->description);
        $this->assertStringContainsString('Consider installing', $healthCheckResult->description);
    }

    public function testRunWithOpenGraphPluginReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(1);
        $this->openGraphCheck->setDatabase($database);

        $healthCheckResult = $this->openGraphCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 enabled plugin', $healthCheckResult->description);
        $this->assertStringContainsString('Open Graph', $healthCheckResult->description);
    }

    public function testRunWithMultipleOpenGraphPluginsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(3);
        $this->openGraphCheck->setDatabase($database);

        $healthCheckResult = $this->openGraphCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('3 enabled plugin', $healthCheckResult->description);
    }
}

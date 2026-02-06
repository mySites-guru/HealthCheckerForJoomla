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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\BrokenLinksCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BrokenLinksCheck::class)]
class BrokenLinksCheckTest extends TestCase
{
    private BrokenLinksCheck $brokenLinksCheck;

    protected function setUp(): void
    {
        $this->brokenLinksCheck = new BrokenLinksCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.broken_links', $this->brokenLinksCheck->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->brokenLinksCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->brokenLinksCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->brokenLinksCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->brokenLinksCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithRedirectComponentNotInstalledReturnsGood(): void
    {
        // First query checks if com_redirect is installed (0 = not installed)
        $database = MockDatabaseFactory::createWithResult(0);
        $this->brokenLinksCheck->setDatabase($database);

        $healthCheckResult = $this->brokenLinksCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not installed', $healthCheckResult->description);
    }

    public function testRunWithNo404ErrorsReturnsGood(): void
    {
        // First query: redirect component installed = 1
        // Second query: count of 404s = 0
        $database = MockDatabaseFactory::createWithSequentialResults([1, 0]);
        $this->brokenLinksCheck->setDatabase($database);

        $healthCheckResult = $this->brokenLinksCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No unhandled 404 errors', $healthCheckResult->description);
    }

    public function testRunWithFew404ErrorsReturnsGood(): void
    {
        // First query: redirect component installed = 1
        // Second query: count of 404s = 20
        $database = MockDatabaseFactory::createWithSequentialResults([1, 20]);
        $this->brokenLinksCheck->setDatabase($database);

        $healthCheckResult = $this->brokenLinksCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('20 unhandled', $healthCheckResult->description);
        $this->assertStringContainsString('Consider creating redirects', $healthCheckResult->description);
    }

    public function testRunWithMany404ErrorsReturnsWarning(): void
    {
        // First query: redirect component installed = 1
        // Second query: count of 404s = 75
        $database = MockDatabaseFactory::createWithSequentialResults([1, 75]);
        $this->brokenLinksCheck->setDatabase($database);

        $healthCheckResult = $this->brokenLinksCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('75 unhandled', $healthCheckResult->description);
        $this->assertStringContainsString('Review', $healthCheckResult->description);
    }

    public function testRunWithExactlyThreshold404sReturnsGood(): void
    {
        // First query: redirect component installed = 1
        // Second query: count of 404s = 50 (threshold is >50)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 50]);
        $this->brokenLinksCheck->setDatabase($database);

        $healthCheckResult = $this->brokenLinksCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithAboveThreshold404sReturnsWarning(): void
    {
        // First query: redirect component installed = 1
        // Second query: count of 404s = 51
        $database = MockDatabaseFactory::createWithSequentialResults([1, 51]);
        $this->brokenLinksCheck->setDatabase($database);

        $healthCheckResult = $this->brokenLinksCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithDatabaseExceptionOnRedirectTableReturnsGood(): void
    {
        // First query: redirect component installed = 1
        // Second query: throws exception (table missing)
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadResult',
                'return' => 1,
            ],
            [
                'method' => 'loadResult',
                'exception' => new \RuntimeException('Table not found'),
            ],
        ]);
        $this->brokenLinksCheck->setDatabase($database);

        $healthCheckResult = $this->brokenLinksCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Could not check', $healthCheckResult->description);
    }

    public function testRunNeverReturnsCritical(): void
    {
        // Even with many 404 errors, should only return warning
        $database = MockDatabaseFactory::createWithSequentialResults([1, 500]);
        $this->brokenLinksCheck->setDatabase($database);

        $healthCheckResult = $this->brokenLinksCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testRunWithZero404sReturnsGoodWithNoErrorsMessage(): void
    {
        // First query: redirect component installed = 1
        // Second query: count of 404s = 0
        $database = MockDatabaseFactory::createWithSequentialResults([1, 0]);
        $this->brokenLinksCheck->setDatabase($database);

        $healthCheckResult = $this->brokenLinksCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No unhandled 404 errors', $healthCheckResult->description);
    }

    public function testRunWithSingle404ErrorReturnsGoodWithCount(): void
    {
        // First query: redirect component installed = 1
        // Second query: count of 404s = 1
        $database = MockDatabaseFactory::createWithSequentialResults([1, 1]);
        $this->brokenLinksCheck->setDatabase($database);

        $healthCheckResult = $this->brokenLinksCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 unhandled', $healthCheckResult->description);
    }
}

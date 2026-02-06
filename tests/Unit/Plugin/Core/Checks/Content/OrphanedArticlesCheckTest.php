<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Content;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Content\OrphanedArticlesCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrphanedArticlesCheck::class)]
class OrphanedArticlesCheckTest extends TestCase
{
    private OrphanedArticlesCheck $orphanedArticlesCheck;

    protected function setUp(): void
    {
        $this->orphanedArticlesCheck = new OrphanedArticlesCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('content.orphaned_articles', $this->orphanedArticlesCheck->getSlug());
    }

    public function testGetCategoryReturnsContent(): void
    {
        $this->assertSame('content', $this->orphanedArticlesCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->orphanedArticlesCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->orphanedArticlesCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->orphanedArticlesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithNoOrphanedArticlesReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->orphanedArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->orphanedArticlesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('All published articles', $healthCheckResult->description);
    }

    public function testRunWithFewOrphanedArticlesReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(5);
        $this->orphanedArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->orphanedArticlesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('5 published articles', $healthCheckResult->description);
        $this->assertStringContainsString('intentional', $healthCheckResult->description);
    }

    public function testRunWithManyOrphanedArticlesReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(15);
        $this->orphanedArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->orphanedArticlesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('15 published articles', $healthCheckResult->description);
    }

    public function testRunWithExactlyTenOrphanedArticlesReturnsGood(): void
    {
        // Boundary test: exactly 10 orphaned articles should return good (>10 triggers warning)
        $database = MockDatabaseFactory::createWithResult(10);
        $this->orphanedArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->orphanedArticlesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('10 published articles', $healthCheckResult->description);
        $this->assertStringContainsString('intentional', $healthCheckResult->description);
    }

    public function testRunWithElevenOrphanedArticlesReturnsWarning(): void
    {
        // Boundary test: 11 orphaned articles should return warning (>10)
        $database = MockDatabaseFactory::createWithResult(11);
        $this->orphanedArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->orphanedArticlesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('11 published articles', $healthCheckResult->description);
    }

    public function testRunReturnsWarningOnDatabaseException(): void
    {
        $database = MockDatabaseFactory::createWithException(new \RuntimeException('Database connection failed'));
        $this->orphanedArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->orphanedArticlesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Unable to check', $healthCheckResult->description);
        $this->assertStringContainsString('Database connection failed', $healthCheckResult->description);
    }
}

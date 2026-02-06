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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Content\UnpublishedCategoryArticlesCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UnpublishedCategoryArticlesCheck::class)]
class UnpublishedCategoryArticlesCheckTest extends TestCase
{
    private UnpublishedCategoryArticlesCheck $unpublishedCategoryArticlesCheck;

    protected function setUp(): void
    {
        $this->unpublishedCategoryArticlesCheck = new UnpublishedCategoryArticlesCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('content.unpublished_category_articles', $this->unpublishedCategoryArticlesCheck->getSlug());
    }

    public function testGetCategoryReturnsContent(): void
    {
        $this->assertSame('content', $this->unpublishedCategoryArticlesCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->unpublishedCategoryArticlesCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->unpublishedCategoryArticlesCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->unpublishedCategoryArticlesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenNoArticlesInUnpublishedCategories(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->unpublishedCategoryArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->unpublishedCategoryArticlesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('published categories', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenArticlesInUnpublishedCategories(): void
    {
        $database = MockDatabaseFactory::createWithResult(5);
        $this->unpublishedCategoryArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->unpublishedCategoryArticlesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('5 published', $healthCheckResult->description);
        $this->assertStringContainsString('unpublished categories', $healthCheckResult->description);
    }

    public function testRunReturnsWarningSingularForOneArticle(): void
    {
        $database = MockDatabaseFactory::createWithResult(1);
        $this->unpublishedCategoryArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->unpublishedCategoryArticlesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 published article is', $healthCheckResult->description);
    }

    public function testRunReturnsWarningPluralForMultipleArticles(): void
    {
        $database = MockDatabaseFactory::createWithResult(3);
        $this->unpublishedCategoryArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->unpublishedCategoryArticlesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('3 published articles are', $healthCheckResult->description);
        $this->assertStringContainsString('are invisible', $healthCheckResult->description);
    }

    public function testRunReturnsWarningOnDatabaseException(): void
    {
        $database = MockDatabaseFactory::createWithException(new \RuntimeException('Database error'));
        $this->unpublishedCategoryArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->unpublishedCategoryArticlesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Unable to check', $healthCheckResult->description);
    }
}

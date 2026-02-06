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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Content\CategoryDepthCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CategoryDepthCheck::class)]
class CategoryDepthCheckTest extends TestCase
{
    private CategoryDepthCheck $categoryDepthCheck;

    protected function setUp(): void
    {
        $this->categoryDepthCheck = new CategoryDepthCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('content.category_depth', $this->categoryDepthCheck->getSlug());
    }

    public function testGetCategoryReturnsContent(): void
    {
        $this->assertSame('content', $this->categoryDepthCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->categoryDepthCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->categoryDepthCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->categoryDepthCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenNoDeepCategories(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->categoryDepthCheck->setDatabase($database);

        $healthCheckResult = $this->categoryDepthCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No categories', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenDeepCategoriesExist(): void
    {
        // First query returns count of deep categories (5), second query returns max level (8)
        $database = MockDatabaseFactory::createWithSequentialResults([5, 8]);
        $this->categoryDepthCheck->setDatabase($database);

        $healthCheckResult = $this->categoryDepthCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('5 categories are', $healthCheckResult->description);
        $this->assertStringContainsString('max depth: 8', $healthCheckResult->description);
    }

    public function testRunReturnsWarningSingularWhenOneCategoryDeep(): void
    {
        // First query returns count of deep categories (1), second query returns max level (7)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 7]);
        $this->categoryDepthCheck->setDatabase($database);

        $healthCheckResult = $this->categoryDepthCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 category is', $healthCheckResult->description);
        $this->assertStringContainsString('max depth: 7', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWithHighMaxDepth(): void
    {
        // First query returns count of deep categories (10), second query returns max level (15)
        $database = MockDatabaseFactory::createWithSequentialResults([10, 15]);
        $this->categoryDepthCheck->setDatabase($database);

        $healthCheckResult = $this->categoryDepthCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('10 categories are', $healthCheckResult->description);
        $this->assertStringContainsString('max depth: 15', $healthCheckResult->description);
        $this->assertStringContainsString('UX issues', $healthCheckResult->description);
    }
}

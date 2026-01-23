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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Content\UncategorizedContentCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UncategorizedContentCheck::class)]
class UncategorizedContentCheckTest extends TestCase
{
    private UncategorizedContentCheck $check;

    protected function setUp(): void
    {
        $this->check = new UncategorizedContentCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('content.uncategorized_content', $this->check->getSlug());
    }

    public function testGetCategoryReturnsContent(): void
    {
        $this->assertSame('content', $this->check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->check->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->check->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsGoodWhenUncategorizedCategoryNotFound(): void
    {
        // First query returns 0 (category not found)
        $database = MockDatabaseFactory::createWithResult(0);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('not found', $result->description);
    }

    public function testRunReturnsGoodWithFewUncategorizedArticles(): void
    {
        // First query returns category ID, second query returns count
        $database = MockDatabaseFactory::createWithSequentialResults([5, 5]); // category id 5, 5 articles
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('5 article(s)', $result->description);
    }

    public function testRunReturnsWarningWithManyUncategorizedArticles(): void
    {
        // First query returns category ID, second query returns count > 10
        $database = MockDatabaseFactory::createWithSequentialResults([5, 15]); // category id 5, 15 articles
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('15 published articles', $result->description);
    }

    public function testRunReturnsGoodWithNoUncategorizedArticles(): void
    {
        // First query returns category ID, second query returns 0
        $database = MockDatabaseFactory::createWithSequentialResults([5, 0]); // category id 5, 0 articles
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('No articles', $result->description);
    }
}

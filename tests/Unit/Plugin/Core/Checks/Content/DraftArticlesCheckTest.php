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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Content\DraftArticlesCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DraftArticlesCheck::class)]
class DraftArticlesCheckTest extends TestCase
{
    private DraftArticlesCheck $draftArticlesCheck;

    protected function setUp(): void
    {
        $this->draftArticlesCheck = new DraftArticlesCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('content.draft_articles', $this->draftArticlesCheck->getSlug());
    }

    public function testGetCategoryReturnsContent(): void
    {
        $this->assertSame('content', $this->draftArticlesCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->draftArticlesCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->draftArticlesCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->draftArticlesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWithNoDrafts(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->draftArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->draftArticlesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('0 unpublished', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWithFewDrafts(): void
    {
        $database = MockDatabaseFactory::createWithResult(15);
        $this->draftArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->draftArticlesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('15 unpublished', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWithManyDrafts(): void
    {
        $database = MockDatabaseFactory::createWithResult(30);
        $this->draftArticlesCheck->setDatabase($database);

        $healthCheckResult = $this->draftArticlesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('30 unpublished', $healthCheckResult->description);
    }
}

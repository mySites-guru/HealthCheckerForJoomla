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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Content\ArchivedContentCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ArchivedContentCheck::class)]
class ArchivedContentCheckTest extends TestCase
{
    private ArchivedContentCheck $archivedContentCheck;

    protected function setUp(): void
    {
        $this->archivedContentCheck = new ArchivedContentCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('content.archived_content', $this->archivedContentCheck->getSlug());
    }

    public function testGetCategoryReturnsContent(): void
    {
        $this->assertSame('content', $this->archivedContentCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->archivedContentCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->archivedContentCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->archivedContentCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWithNoArchivedContent(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->archivedContentCheck->setDatabase($database);

        $healthCheckResult = $this->archivedContentCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No archived', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWithSomeArchivedContent(): void
    {
        $database = MockDatabaseFactory::createWithResult(50);
        $this->archivedContentCheck->setDatabase($database);

        $healthCheckResult = $this->archivedContentCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('50 article(s)', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWithManyArchivedContentButSuggestsReview(): void
    {
        $database = MockDatabaseFactory::createWithResult(150);
        $this->archivedContentCheck->setDatabase($database);

        $healthCheckResult = $this->archivedContentCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('150 articles', $healthCheckResult->description);
        $this->assertStringContainsString('reviewing', $healthCheckResult->description);
    }
}

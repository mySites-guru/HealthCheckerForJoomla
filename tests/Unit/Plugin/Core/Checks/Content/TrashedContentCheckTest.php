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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Content\TrashedContentCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TrashedContentCheck::class)]
class TrashedContentCheckTest extends TestCase
{
    private TrashedContentCheck $trashedContentCheck;

    protected function setUp(): void
    {
        $this->trashedContentCheck = new TrashedContentCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('content.trashed_content', $this->trashedContentCheck->getSlug());
    }

    public function testGetCategoryReturnsContent(): void
    {
        $this->assertSame('content', $this->trashedContentCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->trashedContentCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->trashedContentCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->trashedContentCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithNoTrashedContentReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->trashedContentCheck->setDatabase($database);

        $healthCheckResult = $this->trashedContentCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('0 article(s) in trash', $healthCheckResult->description);
    }

    public function testRunWithSomeTrashedContentReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(25);
        $this->trashedContentCheck->setDatabase($database);

        $healthCheckResult = $this->trashedContentCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithManyTrashedContentReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(75);
        $this->trashedContentCheck->setDatabase($database);

        $healthCheckResult = $this->trashedContentCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('75 articles', $healthCheckResult->description);
        $this->assertStringContainsString('trash', $healthCheckResult->description);
    }
}

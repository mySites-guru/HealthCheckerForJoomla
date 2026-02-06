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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Content\ScheduledContentCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScheduledContentCheck::class)]
class ScheduledContentCheckTest extends TestCase
{
    private ScheduledContentCheck $scheduledContentCheck;

    protected function setUp(): void
    {
        $this->scheduledContentCheck = new ScheduledContentCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('content.scheduled_content', $this->scheduledContentCheck->getSlug());
    }

    public function testGetCategoryReturnsContent(): void
    {
        $this->assertSame('content', $this->scheduledContentCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->scheduledContentCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->scheduledContentCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->scheduledContentCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWithScheduledArticles(): void
    {
        $database = MockDatabaseFactory::createWithResult(5);
        $this->scheduledContentCheck->setDatabase($database);

        $healthCheckResult = $this->scheduledContentCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('5 article(s) scheduled', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWithNoScheduledArticles(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->scheduledContentCheck->setDatabase($database);

        $healthCheckResult = $this->scheduledContentCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No articles scheduled', $healthCheckResult->description);
    }
}

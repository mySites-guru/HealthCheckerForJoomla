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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Content\ExpiredContentCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExpiredContentCheck::class)]
class ExpiredContentCheckTest extends TestCase
{
    private ExpiredContentCheck $expiredContentCheck;

    protected function setUp(): void
    {
        $this->expiredContentCheck = new ExpiredContentCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('content.expired_content', $this->expiredContentCheck->getSlug());
    }

    public function testGetCategoryReturnsContent(): void
    {
        $this->assertSame('content', $this->expiredContentCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->expiredContentCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->expiredContentCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->expiredContentCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenNoExpiredContent(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->expiredContentCheck->setDatabase($database);

        $healthCheckResult = $this->expiredContentCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No published articles', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenExpiredContentExists(): void
    {
        $database = MockDatabaseFactory::createWithResult(5);
        $this->expiredContentCheck->setDatabase($database);

        $healthCheckResult = $this->expiredContentCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('5 published article', $healthCheckResult->description);
        $this->assertStringContainsString('expiry date', $healthCheckResult->description);
    }
}

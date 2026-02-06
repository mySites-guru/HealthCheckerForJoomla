<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Database;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\WaitTimeoutCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WaitTimeoutCheck::class)]
class WaitTimeoutCheckTest extends TestCase
{
    private WaitTimeoutCheck $waitTimeoutCheck;

    protected function setUp(): void
    {
        $this->waitTimeoutCheck = new WaitTimeoutCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.wait_timeout', $this->waitTimeoutCheck->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->waitTimeoutCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->waitTimeoutCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->waitTimeoutCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->waitTimeoutCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenTimeoutIsReasonable(): void
    {
        $timeoutObj = new \stdClass();
        $timeoutObj->Value = 600;  // 10 minutes

        $database = MockDatabaseFactory::createWithObject($timeoutObj);
        $this->waitTimeoutCheck->setDatabase($database);

        $healthCheckResult = $this->waitTimeoutCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('600 seconds', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenTimeoutTooLow(): void
    {
        $timeoutObj = new \stdClass();
        $timeoutObj->Value = 10;  // 10 seconds - too low

        $database = MockDatabaseFactory::createWithObject($timeoutObj);
        $this->waitTimeoutCheck->setDatabase($database);

        $healthCheckResult = $this->waitTimeoutCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('very low', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenTimeoutTooHigh(): void
    {
        $timeoutObj = new \stdClass();
        $timeoutObj->Value = 86400;  // 24 hours - too high

        $database = MockDatabaseFactory::createWithObject($timeoutObj);
        $this->waitTimeoutCheck->setDatabase($database);

        $healthCheckResult = $this->waitTimeoutCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('very high', $healthCheckResult->description);
    }
}

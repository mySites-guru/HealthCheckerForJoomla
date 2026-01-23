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
    private WaitTimeoutCheck $check;

    protected function setUp(): void
    {
        $this->check = new WaitTimeoutCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.wait_timeout', $this->check->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->check->getCategory());
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

    public function testRunReturnsGoodWhenTimeoutIsReasonable(): void
    {
        $timeoutObj = new \stdClass();
        $timeoutObj->Value = 600;  // 10 minutes

        $database = MockDatabaseFactory::createWithObject($timeoutObj);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('600 seconds', $result->description);
    }

    public function testRunReturnsWarningWhenTimeoutTooLow(): void
    {
        $timeoutObj = new \stdClass();
        $timeoutObj->Value = 10;  // 10 seconds - too low

        $database = MockDatabaseFactory::createWithObject($timeoutObj);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('very low', $result->description);
    }

    public function testRunReturnsWarningWhenTimeoutTooHigh(): void
    {
        $timeoutObj = new \stdClass();
        $timeoutObj->Value = 86400;  // 24 hours - too high

        $database = MockDatabaseFactory::createWithObject($timeoutObj);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('very high', $result->description);
    }
}

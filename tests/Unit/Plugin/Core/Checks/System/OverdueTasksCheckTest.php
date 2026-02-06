<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\OverdueTasksCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OverdueTasksCheck::class)]
class OverdueTasksCheckTest extends TestCase
{
    private OverdueTasksCheck $overdueTasksCheck;

    protected function setUp(): void
    {
        $this->overdueTasksCheck = new OverdueTasksCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.overdue_tasks', $this->overdueTasksCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->overdueTasksCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->overdueTasksCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->overdueTasksCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->overdueTasksCheck->setDatabase($database);

        $healthCheckResult = $this->overdueTasksCheck->run();

        $this->assertSame('system.overdue_tasks', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunWithoutDatabaseThrowsException(): void
    {
        // When no database is set, run() should catch the exception and return warning
        $healthCheckResult = $this->overdueTasksCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithNoOverdueTasksReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->overdueTasksCheck->setDatabase($database);

        $healthCheckResult = $this->overdueTasksCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No overdue', $healthCheckResult->description);
    }

    public function testRunWithOneOverdueTaskReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(1);
        $this->overdueTasksCheck->setDatabase($database);

        $healthCheckResult = $this->overdueTasksCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 scheduled task(s) are overdue', $healthCheckResult->description);
    }

    public function testRunWithTenOverdueTasksReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(10);
        $this->overdueTasksCheck->setDatabase($database);

        $healthCheckResult = $this->overdueTasksCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('cron configuration', $healthCheckResult->description);
    }

    public function testRunWithMoreThanTenOverdueTasksReturnsCritical(): void
    {
        $database = MockDatabaseFactory::createWithResult(15);
        $this->overdueTasksCheck->setDatabase($database);

        $healthCheckResult = $this->overdueTasksCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('15 scheduled tasks are overdue', $healthCheckResult->description);
        $this->assertStringContainsString('may not be running', $healthCheckResult->description);
    }

    public function testRunWithExactlyElevenOverdueTasksReturnsCritical(): void
    {
        $database = MockDatabaseFactory::createWithResult(11);
        $this->overdueTasksCheck->setDatabase($database);

        $healthCheckResult = $this->overdueTasksCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testWarningMessageSuggestsCronCheck(): void
    {
        $database = MockDatabaseFactory::createWithResult(5);
        $this->overdueTasksCheck->setDatabase($database);

        $healthCheckResult = $this->overdueTasksCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('cron', strtolower($healthCheckResult->description));
    }
}

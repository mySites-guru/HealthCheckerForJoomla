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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\FailedTasksCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FailedTasksCheck::class)]
class FailedTasksCheckTest extends TestCase
{
    private FailedTasksCheck $failedTasksCheck;

    protected function setUp(): void
    {
        $this->failedTasksCheck = new FailedTasksCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.failed_tasks', $this->failedTasksCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->failedTasksCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->failedTasksCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->failedTasksCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->failedTasksCheck->setDatabase($database);

        $healthCheckResult = $this->failedTasksCheck->run();

        $this->assertSame('system.failed_tasks', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunWithoutDatabaseThrowsException(): void
    {
        // When no database is set, run() should catch the exception and return warning
        $healthCheckResult = $this->failedTasksCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithNoFailedTasksReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->failedTasksCheck->setDatabase($database);

        $healthCheckResult = $this->failedTasksCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('running successfully', $healthCheckResult->description);
    }

    public function testRunWithOneFailedTaskReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(1);
        $this->failedTasksCheck->setDatabase($database);

        $healthCheckResult = $this->failedTasksCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 scheduled task(s) have failed', $healthCheckResult->description);
    }

    public function testRunWithFiveFailedTasksReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(5);
        $this->failedTasksCheck->setDatabase($database);

        $healthCheckResult = $this->failedTasksCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('5 scheduled task(s) have failed', $healthCheckResult->description);
    }

    public function testRunWithMoreThanFiveFailedTasksReturnsWarningWithDifferentMessage(): void
    {
        $database = MockDatabaseFactory::createWithResult(10);
        $this->failedTasksCheck->setDatabase($database);

        $healthCheckResult = $this->failedTasksCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('10 scheduled tasks have failed recently', $healthCheckResult->description);
        $this->assertStringContainsString('Review the task logs', $healthCheckResult->description);
    }

    public function testRunWithManyFailedTasksSuggestsReview(): void
    {
        $database = MockDatabaseFactory::createWithResult(15);
        $this->failedTasksCheck->setDatabase($database);

        $healthCheckResult = $this->failedTasksCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Review the task logs', $healthCheckResult->description);
    }
}

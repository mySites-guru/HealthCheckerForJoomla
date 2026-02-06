<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Users;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\InactiveUsersCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InactiveUsersCheck::class)]
class InactiveUsersCheckTest extends TestCase
{
    private InactiveUsersCheck $inactiveUsersCheck;

    protected function setUp(): void
    {
        $this->inactiveUsersCheck = new InactiveUsersCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.inactive_users', $this->inactiveUsersCheck->getSlug());
    }

    public function testGetCategoryReturnsUsers(): void
    {
        $this->assertSame('users', $this->inactiveUsersCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->inactiveUsersCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->inactiveUsersCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->inactiveUsersCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenNoInactiveUsers(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->inactiveUsersCheck->setDatabase($database);

        $healthCheckResult = $this->inactiveUsersCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('All active users', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenFewInactiveUsers(): void
    {
        $database = MockDatabaseFactory::createWithResult(50);
        $this->inactiveUsersCheck->setDatabase($database);

        $healthCheckResult = $this->inactiveUsersCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('50 user(s) inactive', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenManyInactiveUsers(): void
    {
        $database = MockDatabaseFactory::createWithResult(150);
        $this->inactiveUsersCheck->setDatabase($database);

        $healthCheckResult = $this->inactiveUsersCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('150 users', $healthCheckResult->description);
        $this->assertStringContainsString('reviewing', $healthCheckResult->description);
    }
}

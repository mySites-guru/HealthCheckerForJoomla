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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\BlockedUsersCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BlockedUsersCheck::class)]
class BlockedUsersCheckTest extends TestCase
{
    private BlockedUsersCheck $blockedUsersCheck;

    protected function setUp(): void
    {
        $this->blockedUsersCheck = new BlockedUsersCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.blocked_users', $this->blockedUsersCheck->getSlug());
    }

    public function testGetCategoryReturnsUsers(): void
    {
        $this->assertSame('users', $this->blockedUsersCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->blockedUsersCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->blockedUsersCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->blockedUsersCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithNoBlockedUsersReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->blockedUsersCheck->setDatabase($database);

        $healthCheckResult = $this->blockedUsersCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('0 blocked', $healthCheckResult->description);
    }

    public function testRunWithFewBlockedUsersReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(20);
        $this->blockedUsersCheck->setDatabase($database);

        $healthCheckResult = $this->blockedUsersCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithManyBlockedUsersReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(75);
        $this->blockedUsersCheck->setDatabase($database);

        $healthCheckResult = $this->blockedUsersCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('75 blocked', $healthCheckResult->description);
        $this->assertStringContainsString('cleaning up', $healthCheckResult->description);
    }
}

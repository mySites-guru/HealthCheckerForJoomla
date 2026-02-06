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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\UserGroupsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserGroupsCheck::class)]
class UserGroupsCheckTest extends TestCase
{
    private UserGroupsCheck $userGroupsCheck;

    protected function setUp(): void
    {
        $this->userGroupsCheck = new UserGroupsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.user_groups', $this->userGroupsCheck->getSlug());
    }

    public function testGetCategoryReturnsUsers(): void
    {
        $this->assertSame('users', $this->userGroupsCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->userGroupsCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->userGroupsCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->userGroupsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithFewGroupsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(8);
        $this->userGroupsCheck->setDatabase($database);

        $healthCheckResult = $this->userGroupsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('8 user groups', $healthCheckResult->description);
    }

    public function testRunWithTwentyGroupsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(20);
        $this->userGroupsCheck->setDatabase($database);

        $healthCheckResult = $this->userGroupsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('20 user groups', $healthCheckResult->description);
    }

    public function testRunWithManyGroupsReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(25);
        $this->userGroupsCheck->setDatabase($database);

        $healthCheckResult = $this->userGroupsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('25 user groups', $healthCheckResult->description);
        $this->assertStringContainsString('consolidating', $healthCheckResult->description);
    }

    public function testRunWithExactlyThresholdPlusOneReturnsWarning(): void
    {
        // Threshold is >20, so 21 should trigger warning
        $database = MockDatabaseFactory::createWithResult(21);
        $this->userGroupsCheck->setDatabase($database);

        $healthCheckResult = $this->userGroupsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithDefaultJoomlaGroupsReturnsGood(): void
    {
        // Joomla ships with 9 default groups
        $database = MockDatabaseFactory::createWithResult(9);
        $this->userGroupsCheck->setDatabase($database);

        $healthCheckResult = $this->userGroupsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('9 user groups', $healthCheckResult->description);
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Users;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\DefaultUserGroupCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultUserGroupCheck::class)]
class DefaultUserGroupCheckTest extends TestCase
{
    private DefaultUserGroupCheck $defaultUserGroupCheck;

    protected function setUp(): void
    {
        $this->defaultUserGroupCheck = new DefaultUserGroupCheck();
    }

    protected function tearDown(): void
    {
        ComponentHelper::resetParams();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.default_user_group', $this->defaultUserGroupCheck->getSlug());
    }

    public function testGetCategoryReturnsUsers(): void
    {
        $this->assertSame('users', $this->defaultUserGroupCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->defaultUserGroupCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->defaultUserGroupCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithDangerousAdministratorGroupReturnsCritical(): void
    {
        $registry = new Registry([
            'new_usertype' => 7,
        ]); // Administrator group
        ComponentHelper::setParams('com_users', $registry);

        $healthCheckResult = $this->defaultUserGroupCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Administrator or Super Users', $healthCheckResult->description);
        $this->assertStringContainsString('critical security risk', $healthCheckResult->description);
    }

    public function testRunWithDangerousSuperUsersGroupReturnsCritical(): void
    {
        $registry = new Registry([
            'new_usertype' => 8,
        ]); // Super Users group
        ComponentHelper::setParams('com_users', $registry);

        $healthCheckResult = $this->defaultUserGroupCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Administrator or Super Users', $healthCheckResult->description);
    }

    public function testRunWithSafeRegisteredGroupReturnsGood(): void
    {
        $registry = new Registry([
            'new_usertype' => 2,
        ]); // Registered group
        ComponentHelper::setParams('com_users', $registry);

        $database = MockDatabaseFactory::createWithResult('Registered');
        $this->defaultUserGroupCheck->setDatabase($database);

        $healthCheckResult = $this->defaultUserGroupCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Default user group: Registered', $healthCheckResult->description);
    }

    public function testRunWithSafeGroupReturnsGroupName(): void
    {
        $registry = new Registry([
            'new_usertype' => 3,
        ]); // Author group
        ComponentHelper::setParams('com_users', $registry);

        $database = MockDatabaseFactory::createWithResult('Author');
        $this->defaultUserGroupCheck->setDatabase($database);

        $healthCheckResult = $this->defaultUserGroupCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Author', $healthCheckResult->description);
    }

    public function testRunWithSafeGroupNoNameShowsGroupId(): void
    {
        $registry = new Registry([
            'new_usertype' => 5,
        ]); // Custom group
        ComponentHelper::setParams('com_users', $registry);

        $database = MockDatabaseFactory::createWithResult(null); // Group name not found
        $this->defaultUserGroupCheck->setDatabase($database);

        $healthCheckResult = $this->defaultUserGroupCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('ID 5', $healthCheckResult->description);
    }

    public function testRunWithDefaultGroupValueReturnsGood(): void
    {
        // No params set, should use default value of 2 (Registered)
        $database = MockDatabaseFactory::createWithResult('Registered');
        $this->defaultUserGroupCheck->setDatabase($database);

        $healthCheckResult = $this->defaultUserGroupCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $registry = new Registry([
            'new_usertype' => 2,
        ]);
        ComponentHelper::setParams('com_users', $registry);

        // No database set - should fail with warning for safe group
        $healthCheckResult = $this->defaultUserGroupCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }
}

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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\AdminEmailCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdminEmailCheck::class)]
class AdminEmailCheckTest extends TestCase
{
    private AdminEmailCheck $adminEmailCheck;

    protected function setUp(): void
    {
        $this->adminEmailCheck = new AdminEmailCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.admin_email', $this->adminEmailCheck->getSlug());
    }

    public function testGetCategoryReturnsUsers(): void
    {
        $this->assertSame('users', $this->adminEmailCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->adminEmailCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->adminEmailCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->adminEmailCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithNoSuperAdminsReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithObjectList([]);
        $this->adminEmailCheck->setDatabase($database);

        $healthCheckResult = $this->adminEmailCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No active Super Admin', $healthCheckResult->description);
    }

    public function testRunWithValidEmailsReturnsGood(): void
    {
        $superAdmins = [
            (object) [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@company.com',
            ],
            (object) [
                'id' => 2,
                'username' => 'manager',
                'email' => 'manager@company.com',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($superAdmins);
        $this->adminEmailCheck->setDatabase($database);

        $healthCheckResult = $this->adminEmailCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('2 Super Admin', $healthCheckResult->description);
        $this->assertStringContainsString('valid email', $healthCheckResult->description);
    }

    public function testRunWithEmptyEmailReturnsCritical(): void
    {
        $superAdmins = [
            (object) [
                'id' => 1,
                'username' => 'admin',
                'email' => '',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($superAdmins);
        $this->adminEmailCheck->setDatabase($database);

        $healthCheckResult = $this->adminEmailCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('admin', $healthCheckResult->description);
        $this->assertStringContainsString('no email', $healthCheckResult->description);
    }

    public function testRunWithInvalidEmailFormatReturnsCritical(): void
    {
        $superAdmins = [
            (object) [
                'id' => 1,
                'username' => 'admin',
                'email' => 'not-an-email',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($superAdmins);
        $this->adminEmailCheck->setDatabase($database);

        $healthCheckResult = $this->adminEmailCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('admin', $healthCheckResult->description);
        $this->assertStringContainsString('invalid format', $healthCheckResult->description);
    }

    public function testRunWithExampleDomainReturnsCritical(): void
    {
        $superAdmins = [
            (object) [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@example.com',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($superAdmins);
        $this->adminEmailCheck->setDatabase($database);

        $healthCheckResult = $this->adminEmailCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('admin', $healthCheckResult->description);
        $this->assertStringContainsString('example.com', $healthCheckResult->description);
    }

    public function testRunWithMailinatorDomainReturnsCritical(): void
    {
        $superAdmins = [
            (object) [
                'id' => 1,
                'username' => 'admin',
                'email' => 'admin@mailinator.com',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($superAdmins);
        $this->adminEmailCheck->setDatabase($database);

        $healthCheckResult = $this->adminEmailCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('mailinator.com', $healthCheckResult->description);
    }

    public function testRunWithMultipleInvalidEmailsReturnsCritical(): void
    {
        $superAdmins = [
            (object) [
                'id' => 1,
                'username' => 'admin1',
                'email' => 'admin@example.com',
            ],
            (object) [
                'id' => 2,
                'username' => 'admin2',
                'email' => '',
            ],
            (object) [
                'id' => 3,
                'username' => 'admin3',
                'email' => 'valid@company.com',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($superAdmins);
        $this->adminEmailCheck->setDatabase($database);

        $healthCheckResult = $this->adminEmailCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('admin1', $healthCheckResult->description);
        $this->assertStringContainsString('admin2', $healthCheckResult->description);
    }
}

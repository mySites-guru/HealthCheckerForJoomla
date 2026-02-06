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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\SuperAdminCountCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SuperAdminCountCheck::class)]
class SuperAdminCountCheckTest extends TestCase
{
    private SuperAdminCountCheck $superAdminCountCheck;

    protected function setUp(): void
    {
        $this->superAdminCountCheck = new SuperAdminCountCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.super_admin_count', $this->superAdminCountCheck->getSlug());
    }

    public function testGetCategoryReturnsUsers(): void
    {
        $this->assertSame('users', $this->superAdminCountCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->superAdminCountCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->superAdminCountCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->superAdminCountCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithOneSuperAdminReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(1);
        $this->superAdminCountCheck->setDatabase($database);

        $healthCheckResult = $this->superAdminCountCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 Super Admin', $healthCheckResult->description);
    }

    public function testRunWithThreeSuperAdminsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(3);
        $this->superAdminCountCheck->setDatabase($database);

        $healthCheckResult = $this->superAdminCountCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithFourSuperAdminsReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(4);
        $this->superAdminCountCheck->setDatabase($database);

        $healthCheckResult = $this->superAdminCountCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('4 Super Admin', $healthCheckResult->description);
        $this->assertStringContainsString('reducing', $healthCheckResult->description);
    }

    public function testRunWithSixSuperAdminsReturnsCritical(): void
    {
        $database = MockDatabaseFactory::createWithResult(6);
        $this->superAdminCountCheck->setDatabase($database);

        $healthCheckResult = $this->superAdminCountCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('security risk', $healthCheckResult->description);
    }

    public function testRunWithZeroSuperAdminsReturnsCritical(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->superAdminCountCheck->setDatabase($database);

        $healthCheckResult = $this->superAdminCountCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No active Super Admin', $healthCheckResult->description);
    }
}

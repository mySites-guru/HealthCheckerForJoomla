<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugins\Core\Checks\Users;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\SuperAdminCountCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SuperAdminCountCheck::class)]
class SuperAdminCountCheckTest extends TestCase
{
    public function testGetSlugReturnsCorrectValue(): void
    {
        $superAdminCountCheck = new SuperAdminCountCheck();
        $this->assertSame('users.super_admin_count', $superAdminCountCheck->getSlug());
    }

    public function testGetCategoryReturnsUsers(): void
    {
        $superAdminCountCheck = new SuperAdminCountCheck();
        $this->assertSame('users', $superAdminCountCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $superAdminCountCheck = new SuperAdminCountCheck();
        $this->assertSame('core', $superAdminCountCheck->getProvider());
    }

    public function testRunWithOneSuperAdminReturnsGood(): void
    {
        $superAdminCountCheck = new SuperAdminCountCheck();
        $database = $this->createDatabaseMock(1);
        $superAdminCountCheck->setDatabase($database);

        $healthCheckResult = $superAdminCountCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1', $healthCheckResult->description);
    }

    public function testRunWithThreeSuperAdminsReturnsGood(): void
    {
        $superAdminCountCheck = new SuperAdminCountCheck();
        $database = $this->createDatabaseMock(3);
        $superAdminCountCheck->setDatabase($database);

        $healthCheckResult = $superAdminCountCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithFourSuperAdminsReturnsWarning(): void
    {
        $superAdminCountCheck = new SuperAdminCountCheck();
        $database = $this->createDatabaseMock(4);
        $superAdminCountCheck->setDatabase($database);

        $healthCheckResult = $superAdminCountCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('4', $healthCheckResult->description);
        $this->assertStringContainsString('reducing', $healthCheckResult->description);
    }

    public function testRunWithFiveSuperAdminsReturnsWarning(): void
    {
        $superAdminCountCheck = new SuperAdminCountCheck();
        $database = $this->createDatabaseMock(5);
        $superAdminCountCheck->setDatabase($database);

        $healthCheckResult = $superAdminCountCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithSixSuperAdminsReturnsCritical(): void
    {
        $superAdminCountCheck = new SuperAdminCountCheck();
        $database = $this->createDatabaseMock(6);
        $superAdminCountCheck->setDatabase($database);

        $healthCheckResult = $superAdminCountCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('6', $healthCheckResult->description);
        $this->assertStringContainsString('security risk', $healthCheckResult->description);
    }

    public function testRunWithZeroSuperAdminsReturnsCritical(): void
    {
        $superAdminCountCheck = new SuperAdminCountCheck();
        $database = $this->createDatabaseMock(0);
        $superAdminCountCheck->setDatabase($database);

        $healthCheckResult = $superAdminCountCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No active Super Admin', $healthCheckResult->description);
    }

    public function testRunWithTenSuperAdminsReturnsCritical(): void
    {
        $superAdminCountCheck = new SuperAdminCountCheck();
        $database = $this->createDatabaseMock(10);
        $superAdminCountCheck->setDatabase($database);

        $healthCheckResult = $superAdminCountCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testResultContainsCorrectMetadata(): void
    {
        $superAdminCountCheck = new SuperAdminCountCheck();
        $database = $this->createDatabaseMock(2);
        $superAdminCountCheck->setDatabase($database);

        $healthCheckResult = $superAdminCountCheck->run();

        $this->assertSame('users.super_admin_count', $healthCheckResult->slug);
        $this->assertSame('users', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $superAdminCountCheck = new SuperAdminCountCheck();

        // Don't inject a database - should return warning about missing database
        $healthCheckResult = $superAdminCountCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        // Without database injection, the check should fail gracefully
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    /**
     * Create a mock database that returns a specific count of super admins
     */
    private function createDatabaseMock(int $count): DatabaseInterface
    {
        $query = $this->createMock(QueryInterface::class);
        $query->method('select')
            ->willReturnSelf();
        $query->method('from')
            ->willReturnSelf();
        $query->method('join')
            ->willReturnSelf();
        $query->method('where')
            ->willReturnSelf();

        $db = $this->createMock(DatabaseInterface::class);
        $db->method('getQuery')
            ->willReturn($query);
        $db->method('quoteName')
            ->willReturnCallback(fn(string $name): string => sprintf('`%s`', $name));
        $db->method('setQuery')
            ->willReturnSelf();
        $db->method('loadResult')
            ->willReturn($count);

        return $db;
    }
}

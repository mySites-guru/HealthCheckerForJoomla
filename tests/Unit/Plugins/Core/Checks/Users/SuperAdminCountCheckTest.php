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
        $check = new SuperAdminCountCheck();
        $this->assertSame('users.super_admin_count', $check->getSlug());
    }

    public function testGetCategoryReturnsUsers(): void
    {
        $check = new SuperAdminCountCheck();
        $this->assertSame('users', $check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $check = new SuperAdminCountCheck();
        $this->assertSame('core', $check->getProvider());
    }

    public function testRunWithOneSuperAdminReturnsGood(): void
    {
        $check = new SuperAdminCountCheck();
        $db = $this->createDatabaseMock(1);
        $check->setDatabase($db);

        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('1', $result->description);
    }

    public function testRunWithThreeSuperAdminsReturnsGood(): void
    {
        $check = new SuperAdminCountCheck();
        $db = $this->createDatabaseMock(3);
        $check->setDatabase($db);

        $result = $check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithFourSuperAdminsReturnsWarning(): void
    {
        $check = new SuperAdminCountCheck();
        $db = $this->createDatabaseMock(4);
        $check->setDatabase($db);

        $result = $check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('4', $result->description);
        $this->assertStringContainsString('reducing', $result->description);
    }

    public function testRunWithFiveSuperAdminsReturnsWarning(): void
    {
        $check = new SuperAdminCountCheck();
        $db = $this->createDatabaseMock(5);
        $check->setDatabase($db);

        $result = $check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunWithSixSuperAdminsReturnsCritical(): void
    {
        $check = new SuperAdminCountCheck();
        $db = $this->createDatabaseMock(6);
        $check->setDatabase($db);

        $result = $check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('6', $result->description);
        $this->assertStringContainsString('security risk', $result->description);
    }

    public function testRunWithZeroSuperAdminsReturnsCritical(): void
    {
        $check = new SuperAdminCountCheck();
        $db = $this->createDatabaseMock(0);
        $check->setDatabase($db);

        $result = $check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('No active Super Admin', $result->description);
    }

    public function testRunWithTenSuperAdminsReturnsCritical(): void
    {
        $check = new SuperAdminCountCheck();
        $db = $this->createDatabaseMock(10);
        $check->setDatabase($db);

        $result = $check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testResultContainsCorrectMetadata(): void
    {
        $check = new SuperAdminCountCheck();
        $db = $this->createDatabaseMock(2);
        $check->setDatabase($db);

        $result = $check->run();

        $this->assertSame('users.super_admin_count', $result->slug);
        $this->assertSame('users', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $check = new SuperAdminCountCheck();

        // Don't inject a database - should return warning about missing database
        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        // Without database injection, the check should fail gracefully
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
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
            ->willReturnCallback(fn($name) => "`{$name}`");
        $db->method('setQuery')
            ->willReturnSelf();
        $db->method('loadResult')
            ->willReturn($count);

        return $db;
    }
}

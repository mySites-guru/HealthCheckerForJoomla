<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\TwoFactorAuthCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TwoFactorAuthCheck::class)]
class TwoFactorAuthCheckTest extends TestCase
{
    private TwoFactorAuthCheck $twoFactorAuthCheck;

    protected function setUp(): void
    {
        $this->twoFactorAuthCheck = new TwoFactorAuthCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.two_factor_auth', $this->twoFactorAuthCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->twoFactorAuthCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->twoFactorAuthCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->twoFactorAuthCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->twoFactorAuthCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenNoMfaPluginsEnabled(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->twoFactorAuthCheck->setDatabase($database);

        $healthCheckResult = $this->twoFactorAuthCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No Multi-Factor', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenMfaEnabledButNoSuperAdminsHaveMfa(): void
    {
        // Query 1: Count enabled MFA plugins (returns 2)
        // Query 2: Count total Super Admins (returns 3)
        // Query 3: Count Super Admins with MFA (returns 0)
        $database = MockDatabaseFactory::createWithSequentialResults([2, 3, 0]);
        $this->twoFactorAuthCheck->setDatabase($database);

        $healthCheckResult = $this->twoFactorAuthCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('no Super Admins have MFA configured', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenSomeSuperAdminsHaveMfa(): void
    {
        // Query 1: Count enabled MFA plugins (returns 1)
        // Query 2: Count total Super Admins (returns 5)
        // Query 3: Count Super Admins with MFA (returns 2)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 5, 2]);
        $this->twoFactorAuthCheck->setDatabase($database);

        $healthCheckResult = $this->twoFactorAuthCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('2 of 5 Super Admins have MFA configured', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenAllSuperAdminsHaveMfa(): void
    {
        // Query 1: Count enabled MFA plugins (returns 3)
        // Query 2: Count total Super Admins (returns 2)
        // Query 3: Count Super Admins with MFA (returns 2)
        $database = MockDatabaseFactory::createWithSequentialResults([3, 2, 2]);
        $this->twoFactorAuthCheck->setDatabase($database);

        $healthCheckResult = $this->twoFactorAuthCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('all 2 Super Admin(s) have MFA configured', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWithSingleSuperAdmin(): void
    {
        // Query 1: Count enabled MFA plugins (returns 1)
        // Query 2: Count total Super Admins (returns 1)
        // Query 3: Count Super Admins with MFA (returns 1)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 1, 1]);
        $this->twoFactorAuthCheck->setDatabase($database);

        $healthCheckResult = $this->twoFactorAuthCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('all 1 Super Admin(s) have MFA configured', $healthCheckResult->description);
    }

    public function testRunHandlesZeroSuperAdmins(): void
    {
        // Query 1: Count enabled MFA plugins (returns 2)
        // Query 2: Count total Super Admins (returns 0)
        // Query 3: Count Super Admins with MFA (returns 0)
        $database = MockDatabaseFactory::createWithSequentialResults([2, 0, 0]);
        $this->twoFactorAuthCheck->setDatabase($database);

        $healthCheckResult = $this->twoFactorAuthCheck->run();

        // When totalSuperAdmins is 0 and superAdminsWithMFA is 0, condition (0 < 0) is false
        // So it falls through to the good case
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWithMultipleMfaPluginsButNoUsage(): void
    {
        // Query 1: Count enabled MFA plugins (returns 5)
        // Query 2: Count total Super Admins (returns 10)
        // Query 3: Count Super Admins with MFA (returns 0)
        $database = MockDatabaseFactory::createWithSequentialResults([5, 10, 0]);
        $this->twoFactorAuthCheck->setDatabase($database);

        $healthCheckResult = $this->twoFactorAuthCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('5 MFA plugin(s) enabled', $healthCheckResult->description);
    }
}

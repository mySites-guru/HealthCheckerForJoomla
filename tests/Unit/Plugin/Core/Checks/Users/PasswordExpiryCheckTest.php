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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\PasswordExpiryCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PasswordExpiryCheck::class)]
class PasswordExpiryCheckTest extends TestCase
{
    private PasswordExpiryCheck $passwordExpiryCheck;

    protected function setUp(): void
    {
        $this->passwordExpiryCheck = new PasswordExpiryCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.password_expiry', $this->passwordExpiryCheck->getSlug());
    }

    public function testGetCategoryReturnsUsers(): void
    {
        $this->assertSame('users', $this->passwordExpiryCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->passwordExpiryCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->passwordExpiryCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->passwordExpiryCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithAllPasswordsRecentReturnsGood(): void
    {
        // First query: expired count = 0
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([0, 100]);
        $this->passwordExpiryCheck->setDatabase($database);

        $healthCheckResult = $this->passwordExpiryCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('recently updated', $healthCheckResult->description);
    }

    public function testRunWithFewExpiredPasswordsReturnsGood(): void
    {
        // First query: expired count = 20 (20% of total)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([20, 100]);
        $this->passwordExpiryCheck->setDatabase($database);

        $healthCheckResult = $this->passwordExpiryCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('20 of 100', $healthCheckResult->description);
        $this->assertStringContainsString('acceptable', $healthCheckResult->description);
    }

    public function testRunWithMediumExpiredPasswordsReturnsWarning(): void
    {
        // First query: expired count = 30 (30% of total, above 25% threshold)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([30, 100]);
        $this->passwordExpiryCheck->setDatabase($database);

        $healthCheckResult = $this->passwordExpiryCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('30 of 100', $healthCheckResult->description);
    }

    public function testRunWithHighExpiredPasswordsReturnsWarning(): void
    {
        // First query: expired count = 80 (80% of total, above 75% threshold)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([80, 100]);
        $this->passwordExpiryCheck->setDatabase($database);

        $healthCheckResult = $this->passwordExpiryCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('80%', $healthCheckResult->description);
        $this->assertStringContainsString('password policy', $healthCheckResult->description);
    }

    public function testRunWithExactly25PercentReturnsGood(): void
    {
        // First query: expired count = 25 (exactly 25%)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([25, 100]);
        $this->passwordExpiryCheck->setDatabase($database);

        $healthCheckResult = $this->passwordExpiryCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithAbove25PercentReturnsWarning(): void
    {
        // First query: expired count = 26 (26%, above 25% threshold)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([26, 100]);
        $this->passwordExpiryCheck->setDatabase($database);

        $healthCheckResult = $this->passwordExpiryCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithExactly75PercentReturnsWarningWithPolicyMessage(): void
    {
        // First query: expired count = 75 (exactly 75%)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([75, 100]);
        $this->passwordExpiryCheck->setDatabase($database);

        $healthCheckResult = $this->passwordExpiryCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        // 75% is at threshold, message mentions reviewing policies
        $this->assertStringContainsString('password policies', $healthCheckResult->description);
    }

    public function testRunWithAbove75PercentMentionsImplementingPolicy(): void
    {
        // First query: expired count = 76 (76%, above 75% threshold)
        // Second query: total users = 100
        $database = MockDatabaseFactory::createWithSequentialResults([76, 100]);
        $this->passwordExpiryCheck->setDatabase($database);

        $healthCheckResult = $this->passwordExpiryCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        // Above 75% mentions implementing policy
        $this->assertStringContainsString('implementing', $healthCheckResult->description);
    }

    /**
     * Test that fresh installs with new users don't trigger false positives.
     *
     * On fresh Joomla installs, users have:
     * - registerDate = current timestamp (when user was created)
     * - lastResetTime = NULL (never explicitly reset)
     *
     * These users should NOT be flagged as having expired passwords because
     * their accounts are new. The SQL query now checks registerDate as a
     * fallback when lastResetTime is NULL/zero.
     */
    public function testRunWithNewUsersNullLastResetTimeReturnsGood(): void
    {
        // First query: expired count = 0 (new users with NULL lastResetTime
        // but recent registerDate are excluded by the updated query)
        // Second query: total users = 5
        $database = MockDatabaseFactory::createWithSequentialResults([0, 5]);
        $this->passwordExpiryCheck->setDatabase($database);

        $healthCheckResult = $this->passwordExpiryCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('recently updated', $healthCheckResult->description);
    }
}

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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\PrivacyDashboardCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PrivacyDashboardCheck::class)]
class PrivacyDashboardCheckTest extends TestCase
{
    private PrivacyDashboardCheck $privacyDashboardCheck;

    protected function setUp(): void
    {
        $this->privacyDashboardCheck = new PrivacyDashboardCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.privacy_dashboard', $this->privacyDashboardCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->privacyDashboardCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->privacyDashboardCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->privacyDashboardCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->privacyDashboardCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenPrivacyComponentDisabled(): void
    {
        // First query: check if privacy component is enabled (returns 0)
        $database = MockDatabaseFactory::createWithSequentialResults([0]);
        $this->privacyDashboardCheck->setDatabase($database);

        $healthCheckResult = $this->privacyDashboardCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Privacy component is disabled', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenPendingRequestsExist(): void
    {
        // First query: privacy component enabled (returns 1)
        // Second query: pending privacy requests count (returns 3)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 3]);
        $this->privacyDashboardCheck->setDatabase($database);

        $healthCheckResult = $this->privacyDashboardCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('3 pending privacy request', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenEnabledAndNoPendingRequests(): void
    {
        // First query: privacy component enabled (returns 1)
        // Second query: no pending privacy requests (returns 0)
        $database = MockDatabaseFactory::createWithSequentialResults([1, 0]);
        $this->privacyDashboardCheck->setDatabase($database);

        $healthCheckResult = $this->privacyDashboardCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('enabled with no pending requests', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWithSinglePendingRequest(): void
    {
        // First query: privacy component enabled (returns 1)
        // Second query: 1 pending privacy request
        $database = MockDatabaseFactory::createWithSequentialResults([1, 1]);
        $this->privacyDashboardCheck->setDatabase($database);

        $healthCheckResult = $this->privacyDashboardCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 pending privacy request', $healthCheckResult->description);
    }
}

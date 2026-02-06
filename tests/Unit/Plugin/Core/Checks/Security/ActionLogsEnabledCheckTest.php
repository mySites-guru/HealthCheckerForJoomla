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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\ActionLogsEnabledCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ActionLogsEnabledCheck::class)]
class ActionLogsEnabledCheckTest extends TestCase
{
    private ActionLogsEnabledCheck $actionLogsEnabledCheck;

    protected function setUp(): void
    {
        $this->actionLogsEnabledCheck = new ActionLogsEnabledCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.action_logs_enabled', $this->actionLogsEnabledCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->actionLogsEnabledCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->actionLogsEnabledCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->actionLogsEnabledCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->actionLogsEnabledCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenSystemPluginDisabled(): void
    {
        // System plugin returns enabled = 0 (disabled)
        $database = MockDatabaseFactory::createWithResult(0);
        $this->actionLogsEnabledCheck->setDatabase($database);

        $healthCheckResult = $this->actionLogsEnabledCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('System - Action Logs plugin is disabled', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenSystemPluginNotFound(): void
    {
        // System plugin not found (null result)
        $database = MockDatabaseFactory::createWithResult(null);
        $this->actionLogsEnabledCheck->setDatabase($database);

        $healthCheckResult = $this->actionLogsEnabledCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenSystemPluginEnabled(): void
    {
        // System plugin returns enabled = 1
        $database = MockDatabaseFactory::createWithResult(1);
        $this->actionLogsEnabledCheck->setDatabase($database);

        $healthCheckResult = $this->actionLogsEnabledCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Action Logs system plugin is enabled', $healthCheckResult->description);
    }
}

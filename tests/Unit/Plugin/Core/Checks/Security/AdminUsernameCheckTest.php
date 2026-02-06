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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\AdminUsernameCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdminUsernameCheck::class)]
class AdminUsernameCheckTest extends TestCase
{
    private AdminUsernameCheck $adminUsernameCheck;

    protected function setUp(): void
    {
        $this->adminUsernameCheck = new AdminUsernameCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.admin_username', $this->adminUsernameCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->adminUsernameCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->adminUsernameCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->adminUsernameCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->adminUsernameCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenNoInsecureUsernames(): void
    {
        $database = MockDatabaseFactory::createWithColumn([]);
        $this->adminUsernameCheck->setDatabase($database);

        $healthCheckResult = $this->adminUsernameCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No Super Admin', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenInsecureUsernamesFound(): void
    {
        $database = MockDatabaseFactory::createWithColumn(['admin', 'administrator']);
        $this->adminUsernameCheck->setDatabase($database);

        $healthCheckResult = $this->adminUsernameCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('insecure username', $healthCheckResult->description);
    }
}

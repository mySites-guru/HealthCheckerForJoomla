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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\ApiAuthCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiAuthCheck::class)]
class ApiAuthCheckTest extends TestCase
{
    private ApiAuthCheck $apiAuthCheck;

    protected function setUp(): void
    {
        $this->apiAuthCheck = new ApiAuthCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.api_auth', $this->apiAuthCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->apiAuthCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->apiAuthCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->apiAuthCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->apiAuthCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenNoPluginsEnabled(): void
    {
        // Empty array means no enabled plugins
        $database = MockDatabaseFactory::createWithObjectList([]);
        $this->apiAuthCheck->setDatabase($database);

        $healthCheckResult = $this->apiAuthCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No API authentication', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenPluginsEnabled(): void
    {
        $plugin = new \stdClass();
        $plugin->element = 'token';
        $plugin->enabled = 1;

        $database = MockDatabaseFactory::createWithObjectList([$plugin]);
        $this->apiAuthCheck->setDatabase($database);

        $healthCheckResult = $this->apiAuthCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('token', $healthCheckResult->description);
    }

    public function testRunIgnoresDisabledPlugins(): void
    {
        $plugin = new \stdClass();
        $plugin->element = 'token';
        $plugin->enabled = 0; // Disabled

        $database = MockDatabaseFactory::createWithObjectList([$plugin]);
        $this->apiAuthCheck->setDatabase($database);

        $healthCheckResult = $this->apiAuthCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }
}

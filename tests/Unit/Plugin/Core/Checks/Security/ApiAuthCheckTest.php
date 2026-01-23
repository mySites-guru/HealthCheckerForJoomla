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
    private ApiAuthCheck $check;

    protected function setUp(): void
    {
        $this->check = new ApiAuthCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.api_auth', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->check->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->check->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsWarningWhenNoPluginsEnabled(): void
    {
        // Empty array means no enabled plugins
        $database = MockDatabaseFactory::createWithObjectList([]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('No API authentication', $result->description);
    }

    public function testRunReturnsGoodWhenPluginsEnabled(): void
    {
        $plugin = new \stdClass();
        $plugin->element = 'token';
        $plugin->enabled = 1;

        $database = MockDatabaseFactory::createWithObjectList([$plugin]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('token', $result->description);
    }

    public function testRunIgnoresDisabledPlugins(): void
    {
        $plugin = new \stdClass();
        $plugin->element = 'token';
        $plugin->enabled = 0; // Disabled

        $database = MockDatabaseFactory::createWithObjectList([$plugin]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }
}

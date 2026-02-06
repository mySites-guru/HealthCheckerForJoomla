<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Extensions;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\PluginOrderCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PluginOrderCheck::class)]
class PluginOrderCheckTest extends TestCase
{
    private PluginOrderCheck $pluginOrderCheck;

    protected function setUp(): void
    {
        $this->pluginOrderCheck = new PluginOrderCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.plugin_order', $this->pluginOrderCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->pluginOrderCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->pluginOrderCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->pluginOrderCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->pluginOrderCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('database', strtolower($healthCheckResult->description));
    }

    public function testRunWithNoSystemPluginsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithObjectList([]);
        $this->pluginOrderCheck->setDatabase($database);

        $healthCheckResult = $this->pluginOrderCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithCorrectPluginOrderReturnsGood(): void
    {
        $plugins = [
            (object) [
                'element' => 'session',
                'ordering' => 1,
            ],
            (object) [
                'element' => 'redirect',
                'ordering' => 5,
            ],
            (object) [
                'element' => 'sef',
                'ordering' => 10,
            ],
            (object) [
                'element' => 'cache',
                'ordering' => 15,
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($plugins);
        $this->pluginOrderCheck->setDatabase($database);

        $healthCheckResult = $this->pluginOrderCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('4', $healthCheckResult->description); // 4 plugins
    }

    public function testRunWithSefBeforeRedirectReturnsWarning(): void
    {
        $plugins = [
            (object) [
                'element' => 'sef',
                'ordering' => 1,
            ],
            (object) [
                'element' => 'redirect',
                'ordering' => 5,
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($plugins);
        $this->pluginOrderCheck->setDatabase($database);

        $healthCheckResult = $this->pluginOrderCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('sef', strtolower($healthCheckResult->description));
        $this->assertStringContainsString('redirect', strtolower($healthCheckResult->description));
    }

    public function testRunWithSessionRunningLateReturnsWarning(): void
    {
        $plugins = [
            (object) [
                'element' => 'plugin1',
                'ordering' => 1,
            ],
            (object) [
                'element' => 'plugin2',
                'ordering' => 2,
            ],
            (object) [
                'element' => 'plugin3',
                'ordering' => 3,
            ],
            (object) [
                'element' => 'plugin4',
                'ordering' => 4,
            ],
            (object) [
                'element' => 'plugin5',
                'ordering' => 5,
            ],
            (object) [
                'element' => 'plugin6',
                'ordering' => 6,
            ],
            (object) [
                'element' => 'session',
                'ordering' => 10,
            ], // Too late - 6 plugins before it
        ];
        $database = MockDatabaseFactory::createWithObjectList($plugins);
        $this->pluginOrderCheck->setDatabase($database);

        $healthCheckResult = $this->pluginOrderCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('session', strtolower($healthCheckResult->description));
    }

    public function testRunWithCacheRunningEarlyReturnsWarning(): void
    {
        $plugins = [
            (object) [
                'element' => 'cache',
                'ordering' => 1,
            ],
            (object) [
                'element' => 'redirect',
                'ordering' => 5,
            ],
            (object) [
                'element' => 'sef',
                'ordering' => 10,
            ],
            (object) [
                'element' => 'plugin1',
                'ordering' => 15,
            ],
            (object) [
                'element' => 'plugin2',
                'ordering' => 20,
            ],
            (object) [
                'element' => 'plugin3',
                'ordering' => 25,
            ],
            (object) [
                'element' => 'plugin4',
                'ordering' => 30,
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($plugins);
        $this->pluginOrderCheck->setDatabase($database);

        $healthCheckResult = $this->pluginOrderCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('cache', strtolower($healthCheckResult->description));
    }

    public function testRunWithMultipleIssuesReturnsWarningWithAllIssues(): void
    {
        $plugins = [
            (object) [
                'element' => 'sef',
                'ordering' => 1,
            ],      // Before redirect
            (object) [
                'element' => 'redirect',
                'ordering' => 5,
            ],
            (object) [
                'element' => 'cache',
                'ordering' => 6,
            ],   // Too early
            (object) [
                'element' => 'plugin1',
                'ordering' => 10,
            ],
            (object) [
                'element' => 'plugin2',
                'ordering' => 15,
            ],
            (object) [
                'element' => 'plugin3',
                'ordering' => 20,
            ],
            (object) [
                'element' => 'plugin4',
                'ordering' => 25,
            ],
            (object) [
                'element' => 'plugin5',
                'ordering' => 30,
            ],
            (object) [
                'element' => 'plugin6',
                'ordering' => 35,
            ],
            (object) [
                'element' => 'session',
                'ordering' => 40,
            ], // Too late
        ];
        $database = MockDatabaseFactory::createWithObjectList($plugins);
        $this->pluginOrderCheck->setDatabase($database);

        $healthCheckResult = $this->pluginOrderCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithoutCachePluginReturnsGood(): void
    {
        $plugins = [
            (object) [
                'element' => 'session',
                'ordering' => 1,
            ],
            (object) [
                'element' => 'redirect',
                'ordering' => 5,
            ],
            (object) [
                'element' => 'sef',
                'ordering' => 10,
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($plugins);
        $this->pluginOrderCheck->setDatabase($database);

        $healthCheckResult = $this->pluginOrderCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $healthCheckResult = $this->pluginOrderCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }
}

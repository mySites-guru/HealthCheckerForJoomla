<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugins\Core\Checks\Performance;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use Joomla\CMS\Plugin\PluginHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\PageCacheCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PageCacheCheck::class)]
class PageCacheCheckTest extends TestCase
{
    private PageCacheCheck $pageCacheCheck;

    protected function setUp(): void
    {
        $this->pageCacheCheck = new PageCacheCheck();
        // Reset plugin helper state for test isolation
        PluginHelper::resetEnabled();
    }

    protected function tearDown(): void
    {
        // Reset plugin helper state after each test
        PluginHelper::resetEnabled();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.page_cache', $this->pageCacheCheck->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $this->assertSame('performance', $this->pageCacheCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->pageCacheCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->pageCacheCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithPluginDisabledReturnsWarning(): void
    {
        // PluginHelper::isEnabled returns false by default in stub
        $healthCheckResult = $this->pageCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', $healthCheckResult->description);
    }

    public function testRunWithPluginEnabledAndBrowserCacheEnabledReturnsGood(): void
    {
        // Enable the plugin (element name is 'cache', not 'pagecache')
        PluginHelper::setEnabled('system', 'cache', true);

        $params = json_encode([
            'browsercache' => 1,
        ]);
        $database = MockDatabaseFactory::createWithResult($params);
        $this->pageCacheCheck->setDatabase($database);

        $healthCheckResult = $this->pageCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('browser caching', $healthCheckResult->description);
    }

    public function testRunWithPluginEnabledAndBrowserCacheDisabledReturnsWarning(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('system', 'cache', true);

        $params = json_encode([
            'browsercache' => 0,
        ]);
        $database = MockDatabaseFactory::createWithResult($params);
        $this->pageCacheCheck->setDatabase($database);

        $healthCheckResult = $this->pageCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('browser caching is disabled', $healthCheckResult->description);
    }

    public function testRunWithEmptyParamsReturnsGood(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('system', 'cache', true);

        $database = MockDatabaseFactory::createWithResult('');
        $this->pageCacheCheck->setDatabase($database);

        $healthCheckResult = $this->pageCacheCheck->run();

        // Plugin is enabled, params empty - still good (can't determine browser cache state)
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('enabled', $healthCheckResult->description);
    }

    public function testRunWithInvalidJsonParamsReturnsGood(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('system', 'cache', true);

        $database = MockDatabaseFactory::createWithResult('invalid-json{');
        $this->pageCacheCheck->setDatabase($database);

        $healthCheckResult = $this->pageCacheCheck->run();

        // Plugin is enabled, can't parse params - still good
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithMissingBrowserCacheParamReturnsWarning(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('system', 'cache', true);

        $params = json_encode([
            'other_setting' => 1,
        ]);
        $database = MockDatabaseFactory::createWithResult($params);
        $this->pageCacheCheck->setDatabase($database);

        $healthCheckResult = $this->pageCacheCheck->run();

        // Missing browsercache param defaults to 0, so warning
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $healthCheckResult = $this->pageCacheCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testRunWithNullParamsReturnsGood(): void
    {
        // Enable the plugin so we get to the params check
        PluginHelper::setEnabled('system', 'cache', true);

        $database = MockDatabaseFactory::createWithResult(null);
        $this->pageCacheCheck->setDatabase($database);

        $healthCheckResult = $this->pageCacheCheck->run();

        // Plugin enabled but can't read params - still good
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('enabled', $healthCheckResult->description);
    }

    public function testRunWithBrowserCacheStringValueEnabledReturnsGood(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('system', 'cache', true);

        // Test with string '1' instead of integer
        $params = json_encode([
            'browsercache' => '1',
        ]);
        $database = MockDatabaseFactory::createWithResult($params);
        $this->pageCacheCheck->setDatabase($database);

        $healthCheckResult = $this->pageCacheCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('browser caching', $healthCheckResult->description);
    }

    public function testRunWithBrowserCacheStringValueDisabledReturnsWarning(): void
    {
        // Enable the plugin
        PluginHelper::setEnabled('system', 'cache', true);

        // Test with string '0' instead of integer
        $params = json_encode([
            'browsercache' => '0',
        ]);
        $database = MockDatabaseFactory::createWithResult($params);
        $this->pageCacheCheck->setDatabase($database);

        $healthCheckResult = $this->pageCacheCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('browser caching is disabled', $healthCheckResult->description);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->pageCacheCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame('performance.page_cache', $healthCheckResult->slug);
        $this->assertSame('performance', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsGoodOrWarningStatus(): void
    {
        $healthCheckResult = $this->pageCacheCheck->run();

        // Page cache check only returns Good or Warning, never Critical
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning],
            'Page cache check should return Good or Warning status',
        );
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $healthCheckResult = $this->pageCacheCheck->run();

        $this->assertNotEmpty($healthCheckResult->description);
    }

    public function testResultDescriptionMentionsCache(): void
    {
        $healthCheckResult = $this->pageCacheCheck->run();

        $this->assertStringContainsStringIgnoringCase('cache', $healthCheckResult->description);
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $healthCheckResult = $this->pageCacheCheck->run();

        $array = $healthCheckResult->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('provider', $array);
    }
}

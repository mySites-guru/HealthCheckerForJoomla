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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\XFrameOptionsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(XFrameOptionsCheck::class)]
class XFrameOptionsCheckTest extends TestCase
{
    private XFrameOptionsCheck $xFrameOptionsCheck;

    protected function setUp(): void
    {
        $this->xFrameOptionsCheck = new XFrameOptionsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.x_frame_options', $this->xFrameOptionsCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->xFrameOptionsCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->xFrameOptionsCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->xFrameOptionsCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->xFrameOptionsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenHttpHeadersPluginNotFound(): void
    {
        // loadObject returns null - plugin not found
        $database = MockDatabaseFactory::createWithObject(null);
        $this->xFrameOptionsCheck->setDatabase($database);

        $healthCheckResult = $this->xFrameOptionsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('HTTP Headers plugin not found', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenHttpHeadersPluginDisabled(): void
    {
        $pluginData = (object) [
            'enabled' => 0,
            'params' => '{"xframeoptions":1}',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->xFrameOptionsCheck->setDatabase($database);

        $healthCheckResult = $this->xFrameOptionsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('HTTP Headers plugin is disabled', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenParamsEmpty(): void
    {
        $pluginData = (object) [
            'enabled' => 1,
            'params' => '',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->xFrameOptionsCheck->setDatabase($database);

        $healthCheckResult = $this->xFrameOptionsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not configured', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenParamsIsEmptyArray(): void
    {
        $pluginData = (object) [
            'enabled' => 1,
            'params' => '[]',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->xFrameOptionsCheck->setDatabase($database);

        $healthCheckResult = $this->xFrameOptionsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not configured', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenXFrameOptionsExplicitlyDisabled(): void
    {
        $pluginData = (object) [
            'enabled' => 1,
            'params' => '{"xframeoptions":0}',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->xFrameOptionsCheck->setDatabase($database);

        $healthCheckResult = $this->xFrameOptionsCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('X-Frame-Options is disabled', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenXFrameOptionsEnabled(): void
    {
        $pluginData = (object) [
            'enabled' => 1,
            'params' => '{"xframeoptions":1}',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->xFrameOptionsCheck->setDatabase($database);

        $healthCheckResult = $this->xFrameOptionsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('X-Frame-Options header is enabled', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenXFrameOptionsNotSetInParams(): void
    {
        // When xframeoptions is not explicitly set, it defaults to 1 (enabled)
        $pluginData = (object) [
            'enabled' => 1,
            'params' => '{"some_other_setting":true}',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->xFrameOptionsCheck->setDatabase($database);

        $healthCheckResult = $this->xFrameOptionsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenParamsIsInvalidJson(): void
    {
        $pluginData = (object) [
            'enabled' => 1,
            'params' => 'not valid json',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->xFrameOptionsCheck->setDatabase($database);

        $healthCheckResult = $this->xFrameOptionsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not configured', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenXFrameOptionsExplicitlyDisabledAsString(): void
    {
        $pluginData = (object) [
            'enabled' => 1,
            'params' => '{"xframeoptions":"0"}',
        ];
        $database = MockDatabaseFactory::createWithObject($pluginData);
        $this->xFrameOptionsCheck->setDatabase($database);

        $healthCheckResult = $this->xFrameOptionsCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }
}

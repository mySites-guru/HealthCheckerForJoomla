<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Module\Helper;

use Joomla\CMS\Application\CMSApplication;
use Joomla\Registry\Registry;
use MySitesGuru\HealthChecker\Module\Administrator\Helper\HealthCheckerHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HealthCheckerHelper::class)]
class HealthCheckerHelperTest extends TestCase
{
    private HealthCheckerHelper $healthCheckerHelper;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->healthCheckerHelper = new HealthCheckerHelper();
        $this->cmsApplication = new CMSApplication();
    }

    public function testGetHealthStatsReturnsArray(): void
    {
        $registry = new Registry();

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertIsArray($result);
    }

    public function testGetHealthStatsContainsExpectedKeys(): void
    {
        $registry = new Registry();

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertArrayHasKey('showCritical', $result);
        $this->assertArrayHasKey('showWarning', $result);
        $this->assertArrayHasKey('showGood', $result);
        $this->assertArrayHasKey('enableCache', $result);
        $this->assertArrayHasKey('cacheDuration', $result);
    }

    public function testShowCriticalDefaultsToTrue(): void
    {
        $registry = new Registry();

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertTrue($result['showCritical']);
    }

    public function testShowCriticalCanBeDisabled(): void
    {
        $registry = new Registry([
            'show_critical' => '0',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertFalse($result['showCritical']);
    }

    public function testShowCriticalEnabledWithExplicitOne(): void
    {
        $registry = new Registry([
            'show_critical' => '1',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertTrue($result['showCritical']);
    }

    public function testShowWarningDefaultsToTrue(): void
    {
        $registry = new Registry();

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertTrue($result['showWarning']);
    }

    public function testShowWarningCanBeDisabled(): void
    {
        $registry = new Registry([
            'show_warning' => '0',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertFalse($result['showWarning']);
    }

    public function testShowWarningEnabledWithExplicitOne(): void
    {
        $registry = new Registry([
            'show_warning' => '1',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertTrue($result['showWarning']);
    }

    public function testShowGoodDefaultsToTrue(): void
    {
        $registry = new Registry();

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertTrue($result['showGood']);
    }

    public function testShowGoodCanBeDisabled(): void
    {
        $registry = new Registry([
            'show_good' => '0',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertFalse($result['showGood']);
    }

    public function testShowGoodEnabledWithExplicitOne(): void
    {
        $registry = new Registry([
            'show_good' => '1',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertTrue($result['showGood']);
    }

    public function testEnableCacheDefaultsToTrue(): void
    {
        $registry = new Registry();

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertTrue($result['enableCache']);
    }

    public function testEnableCacheCanBeDisabled(): void
    {
        $registry = new Registry([
            'enable_cache' => '0',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertFalse($result['enableCache']);
    }

    public function testEnableCacheEnabledWithExplicitOne(): void
    {
        $registry = new Registry([
            'enable_cache' => '1',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertTrue($result['enableCache']);
    }

    public function testCacheDurationDefaultsTo900(): void
    {
        $registry = new Registry();

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertSame(900, $result['cacheDuration']);
    }

    public function testCacheDurationCanBeCustomized(): void
    {
        $registry = new Registry([
            'cache_duration' => '1800',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertSame(1800, $result['cacheDuration']);
    }

    public function testCacheDurationWithZeroValue(): void
    {
        $registry = new Registry([
            'cache_duration' => '0',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertSame(0, $result['cacheDuration']);
    }

    public function testCacheDurationIsAlwaysInteger(): void
    {
        $registry = new Registry([
            'cache_duration' => '300',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertIsInt($result['cacheDuration']);
    }

    public function testAllSettingsDisabled(): void
    {
        $registry = new Registry([
            'show_critical' => '0',
            'show_warning' => '0',
            'show_good' => '0',
            'enable_cache' => '0',
            'cache_duration' => '0',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertFalse($result['showCritical']);
        $this->assertFalse($result['showWarning']);
        $this->assertFalse($result['showGood']);
        $this->assertFalse($result['enableCache']);
        $this->assertSame(0, $result['cacheDuration']);
    }

    public function testAllSettingsEnabled(): void
    {
        $registry = new Registry([
            'show_critical' => '1',
            'show_warning' => '1',
            'show_good' => '1',
            'enable_cache' => '1',
            'cache_duration' => '3600',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertTrue($result['showCritical']);
        $this->assertTrue($result['showWarning']);
        $this->assertTrue($result['showGood']);
        $this->assertTrue($result['enableCache']);
        $this->assertSame(3600, $result['cacheDuration']);
    }

    public function testShowCriticalWithNonZeroValueIsTrue(): void
    {
        // Any value other than '0' should result in true
        $registry = new Registry([
            'show_critical' => '2',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertTrue($result['showCritical']);
    }

    public function testEnableCacheOnlyTrueWithExactlyOne(): void
    {
        // enableCache is only true when exactly '1', not any truthy value
        $registry = new Registry([
            'enable_cache' => '2',
        ]);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertFalse($result['enableCache']);
    }

    public function testRegistryWithJsonString(): void
    {
        $jsonParams = json_encode([
            'show_critical' => '0',
            'cache_duration' => '600',
        ]);
        $registry = new Registry($jsonParams);

        $result = $this->healthCheckerHelper->getHealthStats($registry, $this->cmsApplication);

        $this->assertFalse($result['showCritical']);
        $this->assertSame(600, $result['cacheDuration']);
    }
}

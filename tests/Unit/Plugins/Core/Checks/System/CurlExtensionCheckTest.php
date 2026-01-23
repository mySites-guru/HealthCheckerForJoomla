<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugins\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\CurlExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CurlExtensionCheck::class)]
class CurlExtensionCheckTest extends TestCase
{
    public function testGetSlugReturnsCorrectValue(): void
    {
        $check = new CurlExtensionCheck();
        $this->assertSame('system.curl_extension', $check->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $check = new CurlExtensionCheck();
        $this->assertSame('system', $check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $check = new CurlExtensionCheck();
        $this->assertSame('core', $check->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $check = new CurlExtensionCheck();
        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame('system.curl_extension', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsGoodWhenCurlIsLoaded(): void
    {
        // In most PHP environments, cURL is available
        if (! extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension is not loaded');
        }

        $check = new CurlExtensionCheck();
        $result = $check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('cURL', $result->description);
        $this->assertStringContainsString('loaded', $result->description);
    }

    public function testResultDescriptionContainsVersionWhenAvailable(): void
    {
        if (! extension_loaded('curl')) {
            $this->markTestSkipped('cURL extension is not loaded');
        }

        $check = new CurlExtensionCheck();
        $result = $check->run();

        // The description should contain version information
        $this->assertMatchesRegularExpression('/\d+\.\d+/', $result->description);
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $check = new CurlExtensionCheck();

        // Database should be null (not injected)
        $this->assertNull($check->getDatabase());

        // Check should still work without database
        $result = $check->run();
        $this->assertInstanceOf(HealthCheckResult::class, $result);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $check = new CurlExtensionCheck();

        $result1 = $check->run();
        $result2 = $check->run();

        // Results should be the same since cURL availability doesn't change during test
        $this->assertSame($result1->healthStatus, $result2->healthStatus);
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $check = new CurlExtensionCheck();
        $result = $check->run();

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('provider', $array);
    }
}

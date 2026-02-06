<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugins\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\CurlExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CurlExtensionCheck::class)]
class CurlExtensionCheckTest extends TestCase
{
    public function testGetSlugReturnsCorrectValue(): void
    {
        $curlExtensionCheck = new CurlExtensionCheck();
        $this->assertSame('system.curl_extension', $curlExtensionCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $curlExtensionCheck = new CurlExtensionCheck();
        $this->assertSame('system', $curlExtensionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $curlExtensionCheck = new CurlExtensionCheck();
        $this->assertSame('core', $curlExtensionCheck->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $curlExtensionCheck = new CurlExtensionCheck();
        $healthCheckResult = $curlExtensionCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame('system.curl_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $curlExtensionCheck = new CurlExtensionCheck();

        // Database should be null (not injected)
        $this->assertNull($curlExtensionCheck->getDatabase());

        // Check should still work without database
        $healthCheckResult = $curlExtensionCheck->run();
        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $curlExtensionCheck = new CurlExtensionCheck();

        $healthCheckResult = $curlExtensionCheck->run();
        $result2 = $curlExtensionCheck->run();

        // Results should be the same since cURL availability doesn't change during test
        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $curlExtensionCheck = new CurlExtensionCheck();
        $healthCheckResult = $curlExtensionCheck->run();

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

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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\OpcacheCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OpcacheCheck::class)]
class OpcacheCheckTest extends TestCase
{
    public function testGetSlugReturnsCorrectValue(): void
    {
        $opcacheCheck = new OpcacheCheck();
        $this->assertSame('system.opcache', $opcacheCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $opcacheCheck = new OpcacheCheck();
        $this->assertSame('system', $opcacheCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $opcacheCheck = new OpcacheCheck();
        $this->assertSame('core', $opcacheCheck->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $opcacheCheck = new OpcacheCheck();
        $healthCheckResult = $opcacheCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame('system.opcache', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsGoodOrWarningStatus(): void
    {
        $opcacheCheck = new OpcacheCheck();
        $healthCheckResult = $opcacheCheck->run();

        // OPcache check only returns Good or Warning, never Critical
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning],
            'OPcache check should return Good or Warning status',
        );
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $opcacheCheck = new OpcacheCheck();
        $healthCheckResult = $opcacheCheck->run();

        $this->assertNotEmpty($healthCheckResult->description);
    }

    public function testResultDescriptionMentionsOpcache(): void
    {
        $opcacheCheck = new OpcacheCheck();
        $healthCheckResult = $opcacheCheck->run();

        $this->assertStringContainsStringIgnoringCase('opcache', $healthCheckResult->description);
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $opcacheCheck = new OpcacheCheck();

        // Database should be null (not injected)
        $this->assertNull($opcacheCheck->getDatabase());

        // Check should still work without database
        $healthCheckResult = $opcacheCheck->run();
        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $opcacheCheck = new OpcacheCheck();

        $healthCheckResult = $opcacheCheck->run();
        $result2 = $opcacheCheck->run();

        // Results should be the same since OPcache config doesn't change during test
        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $opcacheCheck = new OpcacheCheck();
        $healthCheckResult = $opcacheCheck->run();

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

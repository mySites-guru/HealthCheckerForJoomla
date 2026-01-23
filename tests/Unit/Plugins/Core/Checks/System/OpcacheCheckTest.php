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
        $check = new OpcacheCheck();
        $this->assertSame('system.opcache', $check->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $check = new OpcacheCheck();
        $this->assertSame('system', $check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $check = new OpcacheCheck();
        $this->assertSame('core', $check->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $check = new OpcacheCheck();
        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame('system.opcache', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsGoodOrWarningStatus(): void
    {
        $check = new OpcacheCheck();
        $result = $check->run();

        // OPcache check only returns Good or Warning, never Critical
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning],
            'OPcache check should return Good or Warning status',
        );
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $check = new OpcacheCheck();
        $result = $check->run();

        $this->assertNotEmpty($result->description);
    }

    public function testResultDescriptionMentionsOpcache(): void
    {
        $check = new OpcacheCheck();
        $result = $check->run();

        $this->assertStringContainsStringIgnoringCase('opcache', $result->description);
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $check = new OpcacheCheck();

        // Database should be null (not injected)
        $this->assertNull($check->getDatabase());

        // Check should still work without database
        $result = $check->run();
        $this->assertInstanceOf(HealthCheckResult::class, $result);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $check = new OpcacheCheck();

        $result1 = $check->run();
        $result2 = $check->run();

        // Results should be the same since OPcache config doesn't change during test
        $this->assertSame($result1->healthStatus, $result2->healthStatus);
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $check = new OpcacheCheck();
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

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugins\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\DebugModeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DebugModeCheck::class)]
class DebugModeCheckTest extends TestCase
{
    public function testGetSlugReturnsCorrectValue(): void
    {
        $check = new DebugModeCheck();
        $this->assertSame('security.debug_mode', $check->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $check = new DebugModeCheck();
        $this->assertSame('security', $check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $check = new DebugModeCheck();
        $this->assertSame('core', $check->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $check = new DebugModeCheck();
        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame('security.debug_mode', $result->slug);
        $this->assertSame('security', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsGoodOrWarningStatus(): void
    {
        $check = new DebugModeCheck();
        $result = $check->run();

        // The check returns Good when debug is disabled, Warning when enabled
        // In our test environment, it should return one of these
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning],
            'Debug mode check should return Good or Warning status',
        );
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $check = new DebugModeCheck();
        $result = $check->run();

        $this->assertNotEmpty($result->description);
    }

    public function testResultDescriptionMentionsDebugMode(): void
    {
        $check = new DebugModeCheck();
        $result = $check->run();

        $this->assertStringContainsStringIgnoringCase('debug', $result->description);
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $check = new DebugModeCheck();

        // Database should be null (not injected)
        $this->assertNull($check->getDatabase());

        // Check should still work without database
        $result = $check->run();
        $this->assertInstanceOf(HealthCheckResult::class, $result);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $check = new DebugModeCheck();

        $result1 = $check->run();
        $result2 = $check->run();

        // Results should be the same since debug config doesn't change during test
        $this->assertSame($result1->healthStatus, $result2->healthStatus);
    }
}

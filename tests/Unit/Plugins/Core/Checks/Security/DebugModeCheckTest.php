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
        $debugModeCheck = new DebugModeCheck();
        $this->assertSame('security.debug_mode', $debugModeCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $debugModeCheck = new DebugModeCheck();
        $this->assertSame('security', $debugModeCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $debugModeCheck = new DebugModeCheck();
        $this->assertSame('core', $debugModeCheck->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $debugModeCheck = new DebugModeCheck();
        $healthCheckResult = $debugModeCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame('security.debug_mode', $healthCheckResult->slug);
        $this->assertSame('security', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsGoodOrWarningStatus(): void
    {
        $debugModeCheck = new DebugModeCheck();
        $healthCheckResult = $debugModeCheck->run();

        // The check returns Good when debug is disabled, Warning when enabled
        // In our test environment, it should return one of these
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning],
            'Debug mode check should return Good or Warning status',
        );
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $debugModeCheck = new DebugModeCheck();
        $healthCheckResult = $debugModeCheck->run();

        $this->assertNotEmpty($healthCheckResult->description);
    }

    public function testResultDescriptionMentionsDebugMode(): void
    {
        $debugModeCheck = new DebugModeCheck();
        $healthCheckResult = $debugModeCheck->run();

        $this->assertStringContainsStringIgnoringCase('debug', $healthCheckResult->description);
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $debugModeCheck = new DebugModeCheck();

        // Database should be null (not injected)
        $this->assertNull($debugModeCheck->getDatabase());

        // Check should still work without database
        $healthCheckResult = $debugModeCheck->run();
        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $debugModeCheck = new DebugModeCheck();

        $healthCheckResult = $debugModeCheck->run();
        $result2 = $debugModeCheck->run();

        // Results should be the same since debug config doesn't change during test
        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugins\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\PhpVersionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpVersionCheck::class)]
class PhpVersionCheckTest extends TestCase
{
    public function testGetSlugReturnsCorrectValue(): void
    {
        $phpVersionCheck = new PhpVersionCheck();
        $this->assertSame('system.php_version', $phpVersionCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $phpVersionCheck = new PhpVersionCheck();
        $this->assertSame('system', $phpVersionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $phpVersionCheck = new PhpVersionCheck();
        $this->assertSame('core', $phpVersionCheck->getProvider());
    }

    public function testGetTitleReturnsFallbackSlug(): void
    {
        $phpVersionCheck = new PhpVersionCheck();
        $title = $phpVersionCheck->getTitle();

        // Our mock returns the slug as fallback when translation doesn't exist
        // In production, this would return the translated title
        $this->assertSame('system.php_version', $title);
    }

    public function testRunReturnsGoodForCurrentPhpVersion(): void
    {
        // This test assumes we're running PHP 8.1+ (which is required for this codebase)
        $phpVersionCheck = new PhpVersionCheck();
        $healthCheckResult = $phpVersionCheck->run();

        // Current PHP version should be at least 8.1
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning],
            'PHP version check should return Good or Warning for PHP 8.1+',
        );
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $phpVersionCheck = new PhpVersionCheck();
        $healthCheckResult = $phpVersionCheck->run();

        $this->assertSame('system.php_version', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertNotEmpty($healthCheckResult->description);
        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testResultDescriptionContainsPhpVersion(): void
    {
        $phpVersionCheck = new PhpVersionCheck();
        $healthCheckResult = $phpVersionCheck->run();

        // The description should mention the PHP version
        $this->assertStringContainsString('PHP', $healthCheckResult->description);
    }

    public function testResultDescriptionContainsVersionNumber(): void
    {
        $phpVersionCheck = new PhpVersionCheck();
        $healthCheckResult = $phpVersionCheck->run();

        // Should contain a version number pattern (e.g., "8.1", "8.2", "8.3")
        $this->assertMatchesRegularExpression('/\d+\.\d+/', $healthCheckResult->description);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $phpVersionCheck = new PhpVersionCheck();

        $healthCheckResult = $phpVersionCheck->run();
        $result2 = $phpVersionCheck->run();

        // Results should be the same since PHP version doesn't change during test
        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $phpVersionCheck = new PhpVersionCheck();

        // Database should be null (not injected)
        $this->assertNull($phpVersionCheck->getDatabase());

        // Check should still work without database
        $healthCheckResult = $phpVersionCheck->run();
        $this->assertInstanceOf(
            \MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult::class,
            $healthCheckResult,
        );
    }
}

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
        $check = new PhpVersionCheck();
        $this->assertSame('system.php_version', $check->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $check = new PhpVersionCheck();
        $this->assertSame('system', $check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $check = new PhpVersionCheck();
        $this->assertSame('core', $check->getProvider());
    }

    public function testGetTitleReturnsFallbackSlug(): void
    {
        $check = new PhpVersionCheck();
        $title = $check->getTitle();

        // Our mock returns the slug as fallback when translation doesn't exist
        // In production, this would return the translated title
        $this->assertSame('system.php_version', $title);
    }

    public function testRunReturnsGoodForCurrentPhpVersion(): void
    {
        // This test assumes we're running PHP 8.1+ (which is required for this codebase)
        $check = new PhpVersionCheck();
        $result = $check->run();

        // Current PHP version should be at least 8.1
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning],
            'PHP version check should return Good or Warning for PHP 8.1+',
        );
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $check = new PhpVersionCheck();
        $result = $check->run();

        $this->assertSame('system.php_version', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertNotEmpty($result->description);
        $this->assertNotEmpty($result->title);
    }

    public function testResultDescriptionContainsPhpVersion(): void
    {
        $check = new PhpVersionCheck();
        $result = $check->run();

        // The description should mention the PHP version
        $this->assertStringContainsString('PHP', $result->description);
    }

    public function testResultDescriptionContainsVersionNumber(): void
    {
        $check = new PhpVersionCheck();
        $result = $check->run();

        // Should contain a version number pattern (e.g., "8.1", "8.2", "8.3")
        $this->assertMatchesRegularExpression('/\d+\.\d+/', $result->description);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $check = new PhpVersionCheck();

        $result1 = $check->run();
        $result2 = $check->run();

        // Results should be the same since PHP version doesn't change during test
        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        $this->assertSame($result1->description, $result2->description);
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $check = new PhpVersionCheck();

        // Database should be null (not injected)
        $this->assertNull($check->getDatabase());

        // Check should still work without database
        $result = $check->run();
        $this->assertInstanceOf(
            \MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult::class,
            $result,
        );
    }
}

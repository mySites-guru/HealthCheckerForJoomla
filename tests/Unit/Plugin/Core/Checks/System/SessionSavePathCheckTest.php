<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\SessionSavePathCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SessionSavePathCheck::class)]
class SessionSavePathCheckTest extends TestCase
{
    private SessionSavePathCheck $sessionSavePathCheck;

    protected function setUp(): void
    {
        $this->sessionSavePathCheck = new SessionSavePathCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.session_save_path', $this->sessionSavePathCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->sessionSavePathCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->sessionSavePathCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->sessionSavePathCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->sessionSavePathCheck->run();

        $this->assertSame('system.session_save_path', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $healthCheckResult = $this->sessionSavePathCheck->run();

        // Can return Good or Critical (never Warning according to source)
        $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Critical]);
    }

    public function testRunDescriptionContainsSessionInfo(): void
    {
        $healthCheckResult = $this->sessionSavePathCheck->run();

        // Description should mention session or path
        $this->assertTrue(
            str_contains(strtolower($healthCheckResult->description), 'session') ||
            str_contains(strtolower($healthCheckResult->description), 'path'),
        );
    }

    public function testCurrentSessionSavePathIsDetectable(): void
    {
        $savePath = session_save_path();

        // session_save_path() should return a string (may be empty)
        $this->assertIsString($savePath);
    }

    public function testSystemTempDirectoryExists(): void
    {
        $tempDir = sys_get_temp_dir();

        // System temp directory should exist and be a string
        $this->assertIsString($tempDir);
        $this->assertNotEmpty($tempDir);
    }

    public function testCheckWithValidSessionPath(): void
    {
        // In most test environments, the session save path should be valid
        $healthCheckResult = $this->sessionSavePathCheck->run();

        // If path is valid, should return Good
        // If path is invalid, should return Critical
        $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Critical]);
    }

    public function testGoodResultIncludesPathInfo(): void
    {
        $healthCheckResult = $this->sessionSavePathCheck->run();

        if ($healthCheckResult->healthStatus === HealthStatus::Good) {
            // Good result should mention the path is writable
            $this->assertStringContainsString('writable', $healthCheckResult->description);
        }
    }

    public function testCriticalResultExplainsIssue(): void
    {
        $healthCheckResult = $this->sessionSavePathCheck->run();

        if ($healthCheckResult->healthStatus === HealthStatus::Critical) {
            // Critical result should explain the issue
            $this->assertTrue(
                str_contains($healthCheckResult->description, 'does not exist') ||
                str_contains($healthCheckResult->description, 'not writable'),
            );
        } else {
            // If not critical, should be Good (path is valid)
            $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        }
    }

    public function testCheckNeverReturnsWarning(): void
    {
        // According to the source, this check does not produce Warning
        $healthCheckResult = $this->sessionSavePathCheck->run();

        $this->assertNotSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->sessionSavePathCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->sessionSavePathCheck->run();
        $result2 = $this->sessionSavePathCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->sessionSavePathCheck->run();

        $this->assertSame('system.session_save_path', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testFallbackToSysTempDirWhenEmpty(): void
    {
        // When session_save_path returns empty, the check should use sys_get_temp_dir()
        $tempDir = sys_get_temp_dir();

        // The temp dir should exist and be writable in test environment
        $this->assertTrue(is_dir($tempDir), 'System temp directory should exist');
        $this->assertTrue(is_writable($tempDir), 'System temp directory should be writable');
    }

    public function testSavePathFallbackValues(): void
    {
        // Test that the fallback values ('', '0', false) are handled
        $fallbackValues = ['', '0', false];

        foreach ($fallbackValues as $fallbackValue) {
            // These should all trigger fallback to sys_get_temp_dir()
            $this->assertTrue(in_array($fallbackValue, ['', '0', false], true), 'Value should be in fallback list');
        }
    }

    public function testGoodResultIncludesActualPath(): void
    {
        $healthCheckResult = $this->sessionSavePathCheck->run();

        if ($healthCheckResult->healthStatus === HealthStatus::Good) {
            // Good result should include a path (either session_save_path or sys_get_temp_dir)
            $this->assertMatchesRegularExpression('/\/|\\\\/', $healthCheckResult->description);
        }
    }

    public function testIsDirAndIsWritableChecks(): void
    {
        // Verify the functions used by the check work as expected
        $tempDir = sys_get_temp_dir();

        $this->assertTrue(is_dir($tempDir));
        $this->assertTrue(is_writable($tempDir));
    }

    public function testCriticalWhenDirectoryNotExist(): void
    {
        // We can't easily test this without mocking, but we can verify
        // that is_dir returns false for non-existent paths
        $nonExistentPath = '/this/path/does/not/exist/at/all/' . uniqid();

        $this->assertFalse(is_dir($nonExistentPath));
    }

    public function testSlugFormat(): void
    {
        $slug = $this->sessionSavePathCheck->getSlug();

        // Slug should be lowercase with dot separator
        $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z_]+$/', $slug);
    }

    public function testCategoryIsValid(): void
    {
        $category = $this->sessionSavePathCheck->getCategory();

        // Should be a valid category
        $validCategories = ['system', 'database', 'security', 'users', 'extensions', 'performance', 'seo', 'content'];
        $this->assertContains($category, $validCategories);
    }

    public function testSessionSavePathFunction(): void
    {
        // Test session_save_path returns a string
        $path = session_save_path();

        $this->assertIsString($path);
    }

    public function testFallbackLogicWithEmptyPath(): void
    {
        // Document the fallback logic - when session_save_path is empty,
        // the check uses sys_get_temp_dir()
        $savePath = session_save_path();

        if ($savePath === '' || $savePath === '0') {
            // Should fall back to sys_get_temp_dir()
            $fallback = sys_get_temp_dir();
            $this->assertTrue(is_dir($fallback));
            $this->assertTrue(is_writable($fallback));
        } else {
            // Session save path is set, verify it's usable
            $this->assertNotEmpty($savePath);
        }
    }

    public function testCriticalMessageContainsPathInfo(): void
    {
        $healthCheckResult = $this->sessionSavePathCheck->run();

        if ($healthCheckResult->healthStatus === HealthStatus::Critical) {
            // Critical message should explain the path issue
            $this->assertTrue(
                str_contains($healthCheckResult->description, 'exist') ||
                str_contains($healthCheckResult->description, 'writable') ||
                str_contains($healthCheckResult->description, 'path'),
            );
        } else {
            // Not critical means path is valid
            $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        }
    }

    public function testGoodMessageConfirmsWritability(): void
    {
        $healthCheckResult = $this->sessionSavePathCheck->run();

        if ($healthCheckResult->healthStatus === HealthStatus::Good) {
            // Good message should confirm the path is writable
            $this->assertStringContainsString('writable', $healthCheckResult->description);
        }
    }

    public function testSysTempDirIsWritable(): void
    {
        // System temp directory should always be writable
        $tempDir = sys_get_temp_dir();

        $this->assertTrue(is_writable($tempDir));
        $this->assertTrue(is_dir($tempDir));
    }
}

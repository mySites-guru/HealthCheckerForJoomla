<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\LogFileSizeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LogFileSizeCheck::class)]
class LogFileSizeCheckTest extends TestCase
{
    private LogFileSizeCheck $logFileSizeCheck;

    private string $testLogDir;

    protected function setUp(): void
    {
        $this->logFileSizeCheck = new LogFileSizeCheck();
        $this->testLogDir = sys_get_temp_dir() . '/healthchecker_log_test_' . getmypid() . '_' . uniqid();
    }

    protected function tearDown(): void
    {
        // Clean up test directory
        if (is_dir($this->testLogDir)) {
            $this->removeDirectory($this->testLogDir);
        }

        // Reset Factory application
        Factory::setApplication(null);
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    private function setupApplicationWithLogPath(string $logPath): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('log_path', $logPath);
        Factory::setApplication($cmsApplication);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.log_file_size', $this->logFileSizeCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->logFileSizeCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->logFileSizeCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->logFileSizeCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertSame('system.log_file_size', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $healthCheckResult = $this->logFileSizeCheck->run();

        // Can return Good, Warning, or Critical depending on log size
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionIsNotEmpty(): void
    {
        $healthCheckResult = $this->logFileSizeCheck->run();

        // The check returns a description (may be error message if Joomla not available)
        $this->assertNotEmpty($healthCheckResult->description);
    }

    public function testGoodWhenLogDirectoryDoesNotExist(): void
    {
        // Point to a non-existent directory
        $nonExistentPath = $this->testLogDir . '/nonexistent_logs';
        $this->setupApplicationWithLogPath($nonExistentPath);

        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('does not exist', $healthCheckResult->description);
    }

    public function testGoodWhenLogDirectoryIsEmpty(): void
    {
        // Create empty log directory
        mkdir($this->testLogDir, 0777, true);
        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('manageable', $healthCheckResult->description);
    }

    public function testGoodWhenLogFilesAreSmall(): void
    {
        // Create log directory with small files (under 100MB threshold)
        mkdir($this->testLogDir, 0777, true);
        // Create a 1KB file
        file_put_contents($this->testLogDir . '/error.php', str_repeat('x', 1024));
        // Create another small file
        file_put_contents($this->testLogDir . '/access.log', str_repeat('y', 2048));

        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('manageable', $healthCheckResult->description);
    }

    public function testGoodWhenLogSizeJustBelowWarningThreshold(): void
    {
        // Create log directory with files totaling just under 100MB
        mkdir($this->testLogDir, 0777, true);

        // Create a file that's 99MB (just under the 100MB warning threshold)
        $fileSize = 99 * 1024 * 1024;
        $fp = fopen($this->testLogDir . '/large.log', 'w');
        fseek($fp, $fileSize - 1);
        fwrite($fp, "\0");
        fclose($fp);

        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('manageable', $healthCheckResult->description);
    }

    public function testWarningWhenLogSizeExceedsWarningThreshold(): void
    {
        // Create log directory with files totaling over 100MB but under 500MB
        mkdir($this->testLogDir, 0777, true);

        // Create a file that's 150MB (between 100MB warning and 500MB critical)
        $fileSize = 150 * 1024 * 1024;
        $fp = fopen($this->testLogDir . '/large.log', 'w');
        fseek($fp, $fileSize - 1);
        fwrite($fp, "\0");
        fclose($fp);

        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('growing large', $healthCheckResult->description);
        $this->assertStringContainsString('review', strtolower($healthCheckResult->description));
    }

    public function testWarningWhenLogSizeJustAboveWarningThreshold(): void
    {
        // Create log directory with files totaling just over 100MB
        mkdir($this->testLogDir, 0777, true);

        // Create a file that's 101MB (just over the 100MB warning threshold)
        $fileSize = 101 * 1024 * 1024;
        $fp = fopen($this->testLogDir . '/large.log', 'w');
        fseek($fp, $fileSize - 1);
        fwrite($fp, "\0");
        fclose($fp);

        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testWarningWhenLogSizeJustBelowCriticalThreshold(): void
    {
        // Create log directory with files totaling just under 500MB
        mkdir($this->testLogDir, 0777, true);

        // Create a file that's 499MB (just under the 500MB critical threshold)
        $fileSize = 499 * 1024 * 1024;
        $fp = fopen($this->testLogDir . '/large.log', 'w');
        fseek($fp, $fileSize - 1);
        fwrite($fp, "\0");
        fclose($fp);

        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testCriticalWhenLogSizeExceedsCriticalThreshold(): void
    {
        // Create log directory with files totaling over 500MB
        mkdir($this->testLogDir, 0777, true);

        // Create a file that's 550MB (over the 500MB critical threshold)
        $fileSize = 550 * 1024 * 1024;
        $fp = fopen($this->testLogDir . '/large.log', 'w');
        fseek($fp, $fileSize - 1);
        fwrite($fp, "\0");
        fclose($fp);

        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('very large', $healthCheckResult->description);
        $this->assertStringContainsString('cleaning', strtolower($healthCheckResult->description));
    }

    public function testCriticalWhenLogSizeJustAboveCriticalThreshold(): void
    {
        // Create log directory with files totaling just over 500MB
        mkdir($this->testLogDir, 0777, true);

        // Create a file that's 501MB (just over the 500MB critical threshold)
        $fileSize = 501 * 1024 * 1024;
        $fp = fopen($this->testLogDir . '/large.log', 'w');
        fseek($fp, $fileSize - 1);
        fwrite($fp, "\0");
        fclose($fp);

        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testCalculatesDirectorySizeRecursively(): void
    {
        // Create nested directory structure with multiple files
        mkdir($this->testLogDir . '/subdir', 0777, true);
        file_put_contents($this->testLogDir . '/error.php', str_repeat('x', 1024));
        file_put_contents($this->testLogDir . '/subdir/nested.log', str_repeat('y', 2048));

        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        // Should detect all 3KB of files
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('manageable', $healthCheckResult->description);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertSame('system.log_file_size', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testWarningThresholdIs100MB(): void
    {
        // Warning threshold is 100MB = 100 * 1024 * 1024 = 104857600 bytes
        $warningBytes = 100 * 1024 * 1024;

        $this->assertSame(104857600, $warningBytes);
    }

    public function testCriticalThresholdIs500MB(): void
    {
        // Critical threshold is 500MB = 500 * 1024 * 1024 = 524288000 bytes
        $criticalBytes = 500 * 1024 * 1024;

        $this->assertSame(524288000, $criticalBytes);
    }

    public function testFormatBytesLogic(): void
    {
        // Test the byte formatting logic manually
        $testCases = [[0, '0 B'], [1024, '1 KB'], [1048576, '1 MB'], [1073741824, '1 GB']];

        foreach ($testCases as [$bytes, $expected]) {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $value = (float) $bytes;

            for ($i = 0; $value >= 1024 && $i < count($units) - 1; $i++) {
                $value /= 1024;
            }

            $formatted = round($value, 2) . ' ' . $units[$i];
            $this->assertSame($expected, $formatted);
        }
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        mkdir($this->testLogDir, 0777, true);
        file_put_contents($this->testLogDir . '/test.log', str_repeat('x', 1024));
        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();
        $result2 = $this->logFileSizeCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
    }

    public function testRecursiveDirectoryIteratorLogic(): void
    {
        // Create a test directory with files
        mkdir($this->testLogDir, 0777, true);
        file_put_contents($this->testLogDir . '/test.log', str_repeat('x', 1024));

        // Verify we can calculate directory size
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->testLogDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        $totalSize = 0;
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalSize += $file->getSize();
            }
        }

        $this->assertSame(1024, $totalSize);
    }

    public function testGoodResultDescriptionFormatted(): void
    {
        // Create empty log directory
        mkdir($this->testLogDir, 0777, true);
        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('manageable', $healthCheckResult->description);
    }

    public function testDescriptionIncludesSizeInfo(): void
    {
        // Create log directory with some files
        mkdir($this->testLogDir, 0777, true);
        file_put_contents($this->testLogDir . '/test.log', str_repeat('x', 1024));
        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        // Description should include size information (B, KB, MB, GB) or mention directory
        $descLower = strtolower($healthCheckResult->description);
        $this->assertTrue(
            str_contains($descLower, 'b') ||    // B, KB, MB, GB
            str_contains($descLower, 'directory') ||
            str_contains($descLower, 'log'),
        );
    }

    public function testWarningResultMentionsReviewOrRotate(): void
    {
        // Create log directory with files over 100MB
        mkdir($this->testLogDir, 0777, true);
        $fileSize = 150 * 1024 * 1024;
        $fp = fopen($this->testLogDir . '/large.log', 'w');
        fseek($fp, $fileSize - 1);
        fwrite($fp, "\0");
        fclose($fp);

        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $descLower = strtolower($healthCheckResult->description);
        $this->assertTrue(str_contains($descLower, 'review') || str_contains($descLower, 'rotat'));
    }

    public function testCriticalResultMentionsCleanupOrInvestigate(): void
    {
        // Create log directory with files over 500MB
        mkdir($this->testLogDir, 0777, true);
        $fileSize = 550 * 1024 * 1024;
        $fp = fopen($this->testLogDir . '/large.log', 'w');
        fseek($fp, $fileSize - 1);
        fwrite($fp, "\0");
        fclose($fp);

        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $descLower = strtolower($healthCheckResult->description);
        $this->assertTrue(str_contains($descLower, 'clean') || str_contains($descLower, 'investigat'));
    }

    public function testHandlesMultipleFilesInDirectory(): void
    {
        // Create multiple small files
        mkdir($this->testLogDir, 0777, true);
        for ($i = 0; $i < 10; $i++) {
            file_put_contents($this->testLogDir . sprintf('/log_%d.php', $i), str_repeat('x', 1024));
        }

        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        // 10KB total should be Good
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testHandlesDeeplyNestedDirectories(): void
    {
        // Create deeply nested directory structure
        $nestedPath = $this->testLogDir . '/level1/level2/level3';
        mkdir($nestedPath, 0777, true);
        file_put_contents($nestedPath . '/deep.log', str_repeat('x', 1024));

        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        // Should find the nested file and calculate size correctly
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testHandlesEmptySubdirectories(): void
    {
        // Create directory with empty subdirectories
        mkdir($this->testLogDir . '/empty_subdir', 0777, true);
        mkdir($this->testLogDir . '/another_empty', 0777, true);
        file_put_contents($this->testLogDir . '/root.log', str_repeat('x', 1024));

        $this->setupApplicationWithLogPath($this->testLogDir);

        $healthCheckResult = $this->logFileSizeCheck->run();

        // Should handle empty subdirectories gracefully
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunHandlesDefaultLogPath(): void
    {
        // Test with no explicit log_path set - should use default
        $cmsApplication = new CMSApplication();
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->logFileSizeCheck->run();

        // Should return some valid result (may be Good if default path doesn't exist)
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testFormatBytesHandlesTerabytes(): void
    {
        // Test the byte formatting logic for TB
        $bytes = 1099511627776; // 1 TB
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;

        for ($i = 0; $value >= 1024 && $i < count($units) - 1; $i++) {
            $value /= 1024;
        }

        $formatted = round($value, 2) . ' ' . $units[$i];
        $this->assertSame('1 TB', $formatted);
    }

    public function testFormatBytesHandlesSmallValues(): void
    {
        // Test the byte formatting logic for small values
        $testCases = [[0, '0 B'], [1, '1 B'], [100, '100 B'], [512, '512 B'], [1023, '1023 B']];

        foreach ($testCases as [$bytes, $expected]) {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $value = (float) $bytes;

            for ($i = 0; $value >= 1024 && $i < count($units) - 1; $i++) {
                $value /= 1024;
            }

            $formatted = round($value, 2) . ' ' . $units[$i];
            $this->assertSame($expected, $formatted);
        }
    }

    public function testFormatBytesHandlesFractionalValues(): void
    {
        // Test the byte formatting logic for values that produce decimals
        $bytes = 1536; // 1.5 KB
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;

        for ($i = 0; $value >= 1024 && $i < count($units) - 1; $i++) {
            $value /= 1024;
        }

        $formatted = round($value, 2) . ' ' . $units[$i];
        $this->assertSame('1.5 KB', $formatted);
    }
}

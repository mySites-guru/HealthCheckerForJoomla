<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Log File Size Health Check
 *
 * This check monitors the total size of files in Joomla's log directory.
 * Large log files can indicate problems and consume valuable disk space.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Log files grow continuously and can quickly consume disk space if not managed:
 * - Runaway errors can generate gigabytes of logs in hours
 * - Large log files slow down log analysis and debugging
 * - Full disks cause site failures, database corruption, and data loss
 * - Old logs may contain sensitive information that should be purged
 *
 * Joomla's Task Scheduler can be configured to rotate logs automatically.
 * Consider setting up log rotation if logs grow frequently.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Total log directory size is 100MB or less.
 *       Log files are at a manageable size.
 *
 * WARNING: Total log directory size is between 100MB and 500MB.
 *          Logs are growing large. Consider reviewing and rotating logs.
 *          Check for recurring errors that may be filling the logs.
 *
 * CRITICAL: Total log directory size exceeds 500MB.
 *           Logs are consuming significant disk space. Immediate cleanup
 *           recommended. Investigate what is generating so many log entries.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class LogFileSizeCheck extends AbstractHealthCheck
{
    /**
     * Warning threshold for total log directory size (100MB).
     */
    private const WARNING_BYTES = 100 * 1024 * 1024;

    /**
     * Critical threshold for total log directory size (500MB).
     */
    private const CRITICAL_BYTES = 500 * 1024 * 1024;

    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.log_file_size'
     */
    public function getSlug(): string
    {
        return 'system.log_file_size';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'system'
     */
    public function getCategory(): string
    {
        return 'system';
    }

    /**
     * Perform the log file size health check.
     *
     * Calculates the total size of all files in Joomla's log directory and compares
     * against thresholds. Large log directories can indicate recurring errors and
     * consume significant disk space. Runaway errors can generate gigabytes of logs
     * quickly, potentially filling the disk and causing site failures.
     *
     * Thresholds:
     * - ≤100MB: Good - logs at manageable size
     * - 100MB-500MB: Warning - logs growing large, consider rotation
     * - >500MB: Critical - excessive disk usage, immediate cleanup recommended
     *
     * @return HealthCheckResult Status based on total log directory size
     */
    protected function performCheck(): HealthCheckResult
    {
        // Get configured log path from Joomla config, fallback to default
        $logPath = Factory::getApplication()->get('log_path', JPATH_ADMINISTRATOR . '/logs');

        // If log directory doesn't exist yet, that's actually fine (no logs = no problems)
        if (! is_dir($logPath)) {
            return $this->good('Log directory does not exist or is not accessible.');
        }

        // Need read permissions to calculate size
        if (! is_readable($logPath)) {
            return $this->warning(sprintf('Log directory is not readable: %s', $logPath));
        }

        // Calculate total size of all files in log directory recursively
        try {
            $totalSize = $this->calculateDirectorySize($logPath);
        } catch (\Exception $exception) {
            return $this->warning(sprintf('Unable to calculate log directory size: %s', $exception->getMessage()));
        }

        // Convert bytes to human-readable format (KB, MB, GB, etc.)
        $sizeFormatted = $this->formatBytes($totalSize);

        // Check against critical threshold (500MB)
        if ($totalSize > self::CRITICAL_BYTES) {
            return $this->critical(
                sprintf(
                    'Log directory is very large: %s. Consider cleaning up old logs and investigating what is generating excessive log entries.',
                    $sizeFormatted,
                ),
            );
        }

        // Check against warning threshold (100MB)
        if ($totalSize > self::WARNING_BYTES) {
            return $this->warning(
                sprintf(
                    'Log directory is growing large: %s. Consider reviewing and rotating logs.',
                    $sizeFormatted,
                ),
            );
        }

        return $this->good(sprintf('Log directory size is manageable: %s.', $sizeFormatted));
    }

    /**
     * Calculate total size of all files in a directory recursively.
     *
     * Uses RecursiveDirectoryIterator to traverse all subdirectories and sum
     * the file sizes. Skips directories themselves (. and ..) and only counts
     * actual files.
     *
     * @param string $path The directory path to calculate size for
     *
     * @return int Total size in bytes of all files in directory tree
     */
    private function calculateDirectorySize(string $path): int
    {
        $totalSize = 0;

        // Create recursive iterator to traverse all files in directory tree
        // SKIP_DOTS excludes . and .. directory entries
        // LEAVES_ONLY means we only get files, not intermediate directories
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        // Sum up file sizes (iterator yields SplFileInfo objects)
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $totalSize += $file->getSize();
            }
        }

        return $totalSize;
    }

    /**
     * Format bytes into human-readable string with appropriate unit.
     *
     * Converts raw byte count to the most appropriate unit (B, KB, MB, GB, TB)
     * by repeatedly dividing by 1024 until the value is less than 1024.
     * Returns formatted string with 2 decimal places and unit suffix.
     *
     * @param int $bytes The byte count to format
     *
     * @return string Formatted size string (e.g., "1.23 MB", "456.78 GB")
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = (float) $bytes;

        // Divide by 1024 until we reach the appropriate unit or run out of units
        for ($i = 0; $value >= 1024 && $i < \count($units) - 1; $i++) {
            $value /= 1024;
        }

        return round($value, 2) . ' ' . $units[$i];
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Disk Space Health Check
 *
 * This check monitors available disk space on the server hosting Joomla.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Joomla needs disk space for many operations. Running out of space causes:
 * - Failed file uploads
 * - Corrupted cache files
 * - Failed extension installations
 * - Database backup failures
 * - Session storage issues
 * - Log file write failures (hiding errors)
 *
 * RESULT MEANINGS:
 *
 * GOOD: More than 500MB of free disk space.
 *       Adequate space for normal operations including updates,
 *       backups, and media uploads.
 *
 * WARNING: Between 100MB and 500MB of free space.
 *          Space is running low. Large operations like full backups
 *          or big extension installations may fail. Clean up old
 *          files or increase storage soon.
 *
 * CRITICAL: Less than 100MB of free space.
 *           Immediate attention required. The site may stop functioning.
 *           Updates, uploads, and even normal page caching may fail.
 *           Free up space immediately.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class DiskSpaceCheck extends AbstractHealthCheck
{
    /**
     * Critical threshold for free disk space in bytes (100MB).
     */
    private const CRITICAL_BYTES = 100 * 1024 * 1024; // 100MB

    /**
     * Warning threshold for free disk space in bytes (500MB).
     */
    private const WARNING_BYTES = 500 * 1024 * 1024; // 500MB

    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'system.disk_space'
     */
    public function getSlug(): string
    {
        return 'system.disk_space';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category identifier 'system'
     */
    public function getCategory(): string
    {
        return 'system';
    }

    /**
     * Perform the disk space check.
     *
     * Monitors available disk space on the server partition containing Joomla.
     * Insufficient disk space can cause failed uploads, corrupted cache files,
     * failed backups, and other critical issues.
     *
     * @return HealthCheckResult Critical if below 100MB, Warning if below 500MB, Good otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        // Suppress errors as disk_free_space may fail on some hosting environments
        $freeSpace = @disk_free_space(JPATH_ROOT);

        if ($freeSpace === false) {
            return $this->warning('Unable to determine available disk space.');
        }

        $freeFormatted = $this->formatBytes($freeSpace);

        if ($freeSpace < self::CRITICAL_BYTES) {
            return $this->critical(sprintf('Disk space critically low: %s free.', $freeFormatted));
        }

        if ($freeSpace < self::WARNING_BYTES) {
            return $this->warning(sprintf('Disk space is running low: %s free.', $freeFormatted));
        }

        return $this->good(sprintf('Disk space available: %s free.', $freeFormatted));
    }

    /**
     * Format bytes into human-readable format.
     *
     * Converts raw byte values into appropriate units (B, KB, MB, GB, TB)
     * for better readability in check results.
     *
     * Examples:
     * - 1024 bytes → "1.00 KB"
     * - 1048576 bytes → "1.00 MB"
     * - 1073741824 bytes → "1.00 GB"
     *
     * @param float $bytes The number of bytes to format
     *
     * @return string The formatted string with appropriate unit
     */
    private function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        // Divide by 1024 until we reach the appropriate unit
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}

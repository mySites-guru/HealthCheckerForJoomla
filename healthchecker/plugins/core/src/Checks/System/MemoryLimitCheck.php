<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Memory Limit Health Check
 *
 * This check verifies that PHP has sufficient memory allocated for Joomla operations.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Joomla and its extensions require adequate memory to process requests, handle media,
 * and run complex operations. Insufficient memory causes:
 * - Fatal "out of memory" errors
 * - Failed extension installations
 * - Image processing failures
 * - Incomplete backups and exports
 *
 * RESULT MEANINGS:
 *
 * GOOD: Memory limit is 256M or higher (or unlimited).
 *       Your server has ample memory for most Joomla operations including
 *       large media handling and complex extensions.
 *
 * WARNING: Memory limit is between 128M and 256M.
 *          Basic operations work but you may encounter issues with
 *          large file uploads, image processing, or heavy extensions.
 *
 * CRITICAL: Memory limit is below 128M.
 *           Many Joomla operations will fail. Extension installations,
 *           updates, and media handling are likely to crash.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class MemoryLimitCheck extends AbstractHealthCheck
{
    /**
     * Minimum acceptable memory limit in bytes (128M).
     */
    private const MINIMUM_BYTES = 128 * 1024 * 1024; // 128M

    /**
     * Recommended memory limit in bytes (256M).
     */
    private const RECOMMENDED_BYTES = 256 * 1024 * 1024; // 256M

    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'system.memory_limit'
     */
    public function getSlug(): string
    {
        return 'system.memory_limit';
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
     * Perform the memory limit check.
     *
     * Verifies that PHP has sufficient memory allocated for Joomla operations.
     * Checks against minimum (128M) and recommended (256M) thresholds.
     * Unlimited memory (-1) is considered optimal.
     *
     * @return HealthCheckResult Critical if below 128M, Warning if below 256M, Good otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit === '-1') {
            return $this->good('Memory limit is unlimited.');
        }

        $bytes = $this->convertToBytes($memoryLimit);

        if ($bytes < self::MINIMUM_BYTES) {
            return $this->critical(sprintf('Memory limit (%s) is below the minimum required 128M.', $memoryLimit));
        }

        if ($bytes < self::RECOMMENDED_BYTES) {
            return $this->warning(sprintf('Memory limit (%s) is below the recommended 256M.', $memoryLimit));
        }

        return $this->good(sprintf('Memory limit (%s) meets requirements.', $memoryLimit));
    }

    /**
     * Convert PHP shorthand notation to bytes.
     *
     * Converts values like "128M", "2G", "512K" to their byte equivalents.
     * Handles the following suffixes:
     * - 'G' or 'g': Gigabytes (multiply by 1024^3)
     * - 'M' or 'm': Megabytes (multiply by 1024^2)
     * - 'K' or 'k': Kilobytes (multiply by 1024)
     * - No suffix: Bytes (no conversion)
     *
     * @param string $value The value to convert (e.g., "128M", "2G")
     *
     * @return int The value in bytes, or 0 if empty
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '0') {
            return 0;
        }

        $last = strtolower($value[strlen($value) - 1]);
        $numericValue = (int) $value;

        // Apply multiplier based on suffix
        $numericValue = match ($last) {
            'g' => $numericValue * 1024 * 1024 * 1024,
            'm' => $numericValue * 1024 * 1024,
            'k' => $numericValue * 1024,
            default => $numericValue,
        };

        return $numericValue;
    }
}

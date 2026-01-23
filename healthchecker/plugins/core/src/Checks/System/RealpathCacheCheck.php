<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Realpath Cache Health Check
 *
 * This check verifies the PHP realpath cache configuration. The realpath cache stores
 * the resolved absolute paths of files, avoiding repeated filesystem stat() calls when
 * the same file paths are accessed multiple times during a request.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Joomla includes hundreds of PHP files per request. Without adequate realpath cache,
 * PHP must resolve each file path through the filesystem on every include/require,
 * which involves multiple stat() calls per path. A properly sized realpath cache
 * reduces filesystem overhead and improves performance, especially on slower storage.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The realpath cache is configured with at least 4MB and usage is below 90%.
 * File path resolution is being cached effectively.
 *
 * WARNING: Either the cache size is below the recommended 4MB (may not fit all of
 * Joomla's file paths), or usage exceeds 90% (paths may be evicted, causing repeated
 * filesystem lookups). Consider increasing realpath_cache_size in php.ini.
 *
 * CRITICAL: This check does not produce critical results.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class RealpathCacheCheck extends AbstractHealthCheck
{
    /**
     * Recommended minimum realpath cache size in bytes (4MB).
     *
     * This value represents the minimum cache size needed for typical Joomla
     * installations to effectively cache all resolved file paths.
     */
    private const RECOMMENDED_SIZE = 4 * 1024 * 1024; // 4M

    /**
     * Returns the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.realpath_cache'
     */
    public function getSlug(): string
    {
        return 'system.realpath_cache';
    }

    /**
     * Returns the category this check belongs to.
     *
     * @return string The category identifier 'system'
     */
    public function getCategory(): string
    {
        return 'system';
    }

    /**
     * Performs the realpath cache configuration check.
     *
     * Verifies that the realpath cache is properly sized and not oversubscribed.
     * The realpath cache stores resolved absolute file paths, avoiding repeated
     * filesystem stat() calls when the same paths are accessed multiple times.
     * Joomla includes hundreds of files per request, so adequate cache size is
     * critical for performance, especially on slower storage.
     *
     * @return HealthCheckResult Good status if cache is at least 4MB and usage is below 90%,
     *                            Warning status if cache size is below 4MB or usage exceeds 90%
     */
    protected function performCheck(): HealthCheckResult
    {
        // Get current realpath cache configuration from php.ini
        $cacheSize = ini_get('realpath_cache_size');
        $cacheTtl = ini_get('realpath_cache_ttl');

        if ($cacheSize === false) {
            return $this->warning('Unable to retrieve realpath_cache_size setting.');
        }

        // Convert human-readable size to bytes and get current usage
        $sizeBytes = $this->convertToBytes($cacheSize);
        $cacheInfo = realpath_cache_size();

        // Calculate percentage of cache currently in use
        $usedPercent = $sizeBytes > 0 ? round(($cacheInfo / $sizeBytes) * 100, 1) : 0;

        // Warn if cache size is below recommended minimum
        if ($sizeBytes < self::RECOMMENDED_SIZE) {
            return $this->warning(
                sprintf(
                    'Realpath cache size (%s) is below recommended 4M. Current usage: %s%%.',
                    $cacheSize,
                    $usedPercent,
                ),
            );
        }

        // Warn if cache is nearly full (paths may be evicted)
        if ($usedPercent > 90) {
            return $this->warning(
                sprintf(
                    'Realpath cache is nearly full (%s%% used). Consider increasing realpath_cache_size.',
                    $usedPercent,
                ),
            );
        }

        return $this->good(
            sprintf('Realpath cache: %s configured, %s%% used, TTL %ds.', $cacheSize, $usedPercent, $cacheTtl),
        );
    }

    /**
     * Converts PHP ini size notation to bytes.
     *
     * Handles size suffixes: K (kilobytes), M (megabytes), G (gigabytes).
     * If no suffix is present, treats the value as bytes.
     *
     * @param string $value The size value from php.ini (e.g., '4M', '512K', '1G')
     *
     * @return int The size in bytes
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);

        // Handle empty values
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

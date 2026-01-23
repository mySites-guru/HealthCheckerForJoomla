<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * OPcache Health Check
 *
 * This check verifies that PHP's OPcache extension is installed, enabled, and has
 * adequate memory available. OPcache stores precompiled PHP bytecode in shared memory,
 * eliminating the need to parse and compile PHP files on each request.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * OPcache typically provides 2-3x performance improvement for PHP applications like
 * Joomla. Without OPcache, PHP must parse and compile every PHP file on every request,
 * which significantly increases CPU usage and response times. This is one of the most
 * impactful performance optimizations for any Joomla site.
 *
 * RESULT MEANINGS:
 *
 * GOOD: OPcache is enabled and memory usage is below 90%. The bytecode cache is
 * functioning properly and has room for additional cached scripts.
 *
 * WARNING: One of several conditions: OPcache extension is not loaded (major performance
 * impact), OPcache is installed but disabled in php.ini, unable to retrieve status, or
 * memory usage exceeds 90% (scripts may be evicted from cache, reducing effectiveness).
 *
 * CRITICAL: This check does not produce critical results.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class OpcacheCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.opcache'
     */
    public function getSlug(): string
    {
        return 'system.opcache';
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
     * Performs the OPcache configuration and health check.
     *
     * Verifies that the OPcache extension is loaded, enabled, and properly configured.
     * Checks memory usage to ensure the bytecode cache is not oversubscribed.
     * OPcache stores precompiled PHP bytecode in shared memory, providing significant
     * performance improvements (typically 2-3x faster) by eliminating repeated parsing
     * and compilation of PHP files.
     *
     * @return HealthCheckResult Good status if OPcache is enabled with healthy memory usage (<90%),
     *                            Warning status if OPcache is not loaded, disabled, status unavailable,
     *                            or memory usage exceeds 90% threshold
     */
    protected function performCheck(): HealthCheckResult
    {
        // Verify OPcache extension is loaded
        if (! extension_loaded('Zend OPcache')) {
            return $this->warning('OPcache extension is not loaded. Performance will be significantly impacted.');
        }

        // Check if OPcache is enabled in php.ini
        if (in_array(ini_get('opcache.enable'), ['', '0'], true)) {
            return $this->warning('OPcache is installed but not enabled. Enable it for better performance.');
        }

        // Retrieve current OPcache status (false = don't include script data for performance)
        $status = opcache_get_status(false);

        if ($status === false) {
            return $this->warning('Unable to get OPcache status.');
        }

        // Check if memory_usage data is available and valid
        if (! isset($status['memory_usage']) || ! is_array($status['memory_usage'])) {
            return $this->good('OPcache is enabled (memory statistics not available).');
        }

        $memoryUsage = $status['memory_usage'];

        // Validate required keys exist
        if (! isset($memoryUsage['used_memory'], $memoryUsage['free_memory'])) {
            return $this->good('OPcache is enabled (memory statistics incomplete).');
        }

        $usedMemory = $memoryUsage['used_memory'];
        $freeMemory = $memoryUsage['free_memory'];

        // Guard against invalid memory values (negative, zero, or unreasonable)
        if ($usedMemory < 0 || $freeMemory < 0 || ($usedMemory + $freeMemory) <= 0) {
            return $this->good('OPcache is enabled (memory statistics unavailable in this context).');
        }

        $totalMemory = $usedMemory + $freeMemory;
        $usedPercent = round(($usedMemory / $totalMemory) * 100, 1);

        // Sanity check: percentage should be between 0 and 100
        if ($usedPercent < 0 || $usedPercent > 100) {
            return $this->good('OPcache is enabled (memory statistics unreliable).');
        }

        // Warn if memory usage is critically high (scripts may be evicted)
        if ($usedPercent > 90) {
            return $this->warning(
                sprintf(
                    'OPcache memory usage is high (%s%%). Consider increasing opcache.memory_consumption.',
                    $usedPercent,
                ),
            );
        }

        return $this->good(sprintf('OPcache is enabled and healthy (%s%% memory used).', $usedPercent));
    }
}

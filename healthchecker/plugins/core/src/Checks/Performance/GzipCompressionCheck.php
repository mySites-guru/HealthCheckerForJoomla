<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Gzip Compression Health Check
 *
 * This check verifies whether Joomla's built-in Gzip page compression is enabled
 * in Global Configuration.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Gzip compression reduces the size of HTML, CSS, and JavaScript sent to browsers,
 * typically by 60-80%. This reduces bandwidth usage and significantly improves
 * page load times, especially for users on slower connections. It also reduces
 * hosting costs if bandwidth is metered.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Gzip compression is enabled. Page output is compressed before being
 * sent to browsers, reducing transfer sizes and improving load times.
 *
 * WARNING: Gzip compression is disabled. Enable it in Global Configuration
 * for better performance. If the zlib PHP extension is not available,
 * Gzip cannot be enabled until the extension is installed.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class GzipCompressionCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'performance.gzip_compression'
     */
    public function getSlug(): string
    {
        return 'performance.gzip_compression';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category identifier 'performance'
     */
    public function getCategory(): string
    {
        return 'performance';
    }

    /**
     * Perform the Gzip compression health check.
     *
     * This method verifies whether Joomla's built-in Gzip page compression is enabled
     * in Global Configuration. Gzip compression reduces the size of HTML, CSS, and
     * JavaScript sent to browsers by 60-80%, significantly improving page load times
     * and reducing bandwidth usage.
     *
     * The check performs these steps:
     * 1. Reads the 'gzip' configuration value (1 = enabled, 0 = disabled)
     * 2. If disabled, checks if PHP zlib extension is available
     * 3. Reports status and provides actionable guidance
     *
     * Returns:
     * - GOOD: Gzip compression is enabled
     * - WARNING: Compression disabled, or disabled and zlib extension unavailable
     *
     * Note: Gzip compression requires the PHP zlib extension to be installed and loaded.
     *
     * @return HealthCheckResult The result indicating Gzip compression status
     */
    protected function performCheck(): HealthCheckResult
    {
        // Get gzip setting from Global Configuration (default: 0/disabled)
        $gzip = (int) Factory::getApplication()->get('gzip', 0);

        if ($gzip === 0) {
            // Check if the required PHP extension is available
            if (! extension_loaded('zlib')) {
                return $this->warning('Gzip compression is disabled and zlib extension is not available.');
            }

            return $this->warning(
                'Gzip compression is disabled. Enable it in Global Configuration for better performance.',
            );
        }

        return $this->good('Gzip compression is enabled.');
    }
}

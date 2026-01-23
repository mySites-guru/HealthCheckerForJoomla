<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * System Cache Health Check
 *
 * This check verifies whether Joomla's system caching is enabled and reports
 * which cache handler is being used (file, redis, memcached, etc.).
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Caching significantly improves site performance by storing pre-computed data
 * and reducing database queries, file system access, and processing time.
 * Disabling caching in production environments leads to slower page loads,
 * increased server load, and poor user experience.
 *
 * RESULT MEANINGS:
 *
 * GOOD: System caching is enabled. The cache handler (file, redis, memcached, etc.)
 * is actively storing and serving cached content to improve performance.
 *
 * WARNING: System caching is disabled. For production sites, enable caching in
 * Global Configuration to improve performance. Caching may be intentionally
 * disabled during development.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class SystemCacheCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check identifier in format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'performance.system_cache';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug
     */
    public function getCategory(): string
    {
        return 'performance';
    }

    /**
     * Perform the system cache configuration check.
     *
     * Verifies that either conservative caching (Global Configuration) or progressive
     * caching (System - Cache plugin) is enabled. Caching is critical for production
     * performance by storing pre-computed data and reducing database queries.
     *
     * Performance impact:
     * - Caching reduces database queries by 50-90% for cached content
     * - Module rendering, component output, and queries are cached
     * - Different handlers offer different performance characteristics:
     *   - File: Simple, no dependencies, moderate performance
     *   - Redis/Memcached: Fastest, requires additional services
     *   - APCu: Fast, server-specific, limited by PHP memory
     *
     * Cache configuration checks:
     * - Global Configuration 'caching' setting (conservative mode)
     * - System - Cache plugin status (progressive mode)
     * - Reports which cache handler is in use (file, redis, memcached, etc.)
     *
     * @return HealthCheckResult Returns WARNING if caching fully disabled,
     *                           GOOD if enabled with handler information
     */
    /**
     * Perform the System Cache health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        // Check Global Configuration caching setting (0=Off, 1=Conservative, 2=Progressive)
        // Note: Joomla 5 default is 2 (Progressive) - not stored in DB if using default
        $cacheEnabled = Factory::getApplication()->get('caching', 2);

        // Check if System - Cache plugin is enabled (provides progressive caching)
        $cachePlugin = PluginHelper::isEnabled('system', 'cache');

        // Both Global Configuration and plugin disabled - no caching active
        // This significantly impacts performance on production sites
        if (! $cacheEnabled && ! $cachePlugin) {
            return $this->warning(
                'System caching is disabled. Enable caching for better performance in production.',
            );
        }

        // At least one caching mechanism is enabled
        // Report which handler is being used for cache storage
        // Note: Joomla 5 default is empty string (auto-detect) - not stored in DB if using default
        $cacheHandler = Factory::getApplication()->get('cache_handler', '') ?: 'auto-detect';

        return $this->good(sprintf('System caching is enabled using the %s handler.', $cacheHandler));
    }
}

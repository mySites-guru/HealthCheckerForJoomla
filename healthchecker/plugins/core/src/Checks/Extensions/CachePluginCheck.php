<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Cache Plugin Health Check
 *
 * This check examines the configuration of the System - Page Cache plugin
 * and its relationship with Joomla's global caching settings.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The Page Cache plugin can dramatically improve performance for guest visitors
 * by serving cached full-page HTML instead of regenerating pages for each request.
 * However, it only functions when system caching is also enabled. Misconfigured
 * caching settings can lead to either poor performance or unexpected behavior.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Page caching is properly configured. Either the Page Cache plugin is
 * enabled with system caching, or system caching is enabled without the plugin
 * (which still provides component-level caching benefits).
 *
 * WARNING: Caching configuration issues detected. Either both caching options
 * are disabled (missing performance optimization), or the Page Cache plugin
 * is enabled but system caching is disabled (plugin will not function).
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class CachePluginCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'extensions.cache_plugin'
     */
    public function getSlug(): string
    {
        return 'extensions.cache_plugin';
    }

    /**
     * Returns the category this check belongs to.
     *
     * @return string The category slug 'extensions'
     */
    public function getCategory(): string
    {
        return 'extensions';
    }

    /**
     * Performs the cache plugin health check.
     *
     * This method examines the System - Page Cache plugin (plg_system_cache) and its
     * relationship with Joomla's global caching configuration. The Page Cache plugin
     * requires system caching to be enabled in order to function.
     *
     * System caching controls component-level caching, while the Page Cache plugin
     * provides full-page HTML caching for guest visitors, which is more aggressive
     * and can significantly improve performance.
     *
     * Checks four possible states:
     * 1. Both disabled - Missing performance optimization
     * 2. Plugin enabled, system cache disabled - Plugin won't work
     * 3. System cache enabled, plugin disabled - Component caching works, but missing page cache
     * 4. Both enabled - Optimal configuration
     *
     * @return HealthCheckResult WARNING if caching is misconfigured, GOOD if properly configured
     */
    protected function performCheck(): HealthCheckResult
    {
        // Check if the System - Page Cache plugin (plg_system_cache) is enabled
        $cachePluginEnabled = PluginHelper::isEnabled('system', 'cache');

        // Check global system caching setting from Global Configuration
        $systemCacheEnabled = (bool) Factory::getApplication()->get('caching', 0);

        // Both disabled - no caching at all
        if (! $cachePluginEnabled && ! $systemCacheEnabled) {
            return $this->warning(
                'Page cache plugin is disabled and system caching is off. Enable caching for better performance.',
            );
        }

        // Plugin enabled but system cache disabled - plugin won't function
        if ($cachePluginEnabled && ! $systemCacheEnabled) {
            return $this->warning(
                'Page cache plugin is enabled but system caching is disabled. Enable system caching for the plugin to work.',
            );
        }

        // System cache enabled but plugin disabled - basic caching works
        if (! $cachePluginEnabled && $systemCacheEnabled) {
            return $this->good(
                'System caching is enabled. Page cache plugin is available for additional performance gains.',
            );
        }

        // Both enabled - optimal configuration. Get additional cache configuration details.
        // Cache handler determines storage mechanism (file, memcached, redis, etc.)
        $cacheHandler = Factory::getApplication()->get('cache_handler', 'file');
        // Cache time is the TTL in minutes for cached content
        $cacheTime = Factory::getApplication()->get('cachetime', 15);

        return $this->good(
            sprintf(
                'Page cache plugin is enabled with %s handler (cache time: %d minutes).',
                $cacheHandler,
                $cacheTime,
            ),
        );
    }
}

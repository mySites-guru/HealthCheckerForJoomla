<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Page Cache Health Check
 *
 * This check verifies whether the System - Page Cache plugin is enabled,
 * which provides full-page caching for guest visitors.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The Page Cache plugin stores complete rendered pages for guest visitors,
 * bypassing most of Joomla's processing on subsequent requests. This can
 * dramatically improve performance for sites with high guest traffic by
 * reducing database queries and PHP execution to near zero for cached pages.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The System - Page Cache plugin is enabled. Guest visitors will receive
 * cached full-page responses for significantly faster page loads.
 *
 * WARNING: The System - Page Cache plugin is disabled. For production sites
 * with significant guest traffic, enabling this plugin can provide substantial
 * performance improvements. Note that it only caches pages for guests, not
 * logged-in users.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance;

use Joomla\CMS\Plugin\PluginHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class PageCacheCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check identifier in format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'performance.page_cache';
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
     * Perform the page cache plugin status check.
     *
     * Verifies whether the System - Page Cache plugin is enabled, which provides
     * full-page caching for guest visitors. This is one of the most impactful
     * performance optimizations available in Joomla.
     *
     * Performance impact:
     * - Bypasses database queries and PHP execution for cached pages
     * - Can reduce page generation time from 500ms+ to under 10ms
     * - Dramatically reduces server load under high guest traffic
     * - Only caches pages for non-logged-in users (guests)
     *
     * Note: This check only verifies if the plugin is enabled, not the actual
     * cache configuration or storage handler settings.
     *
     * @return HealthCheckResult Returns WARNING if disabled, GOOD if enabled
     */
    protected function performCheck(): HealthCheckResult
    {
        // Check if the System - Page Cache plugin is enabled
        // This provides full-page caching for guest visitors only
        $isEnabled = PluginHelper::isEnabled('system', 'pagecache');

        // Plugin disabled - significant performance opportunity missed
        if (! $isEnabled) {
            return $this->warning(
                'System - Page Cache plugin is disabled. Enable it in production for improved performance on guest page loads.',
            );
        }

        // Plugin enabled - guest visitors will receive cached pages
        return $this->good('System - Page Cache plugin is enabled.');
    }
}

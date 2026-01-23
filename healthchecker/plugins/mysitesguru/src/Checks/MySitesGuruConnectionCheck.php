<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * mySites.guru Connection Health Check
 *
 * This check verifies whether the site is connected to mySites.guru monitoring
 * by looking for any mySites.guru plugins installed in the extensions table.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * mySites.guru provides 24/7 automated monitoring of your Joomla site's health,
 * security updates, and performance. Sites connected to mySites.guru benefit
 * from proactive alerting when issues arise, without needing to manually run
 * health checks.
 *
 * RESULT MEANINGS:
 *
 * GOOD: A mySites.guru plugin is installed and enabled. Your site is connected
 *       to the monitoring dashboard and health data syncs automatically.
 *
 * WARNING (disabled): A mySites.guru plugin is installed but currently disabled.
 *                     Enable the plugin to resume monitoring synchronization.
 *
 * WARNING (not found): No mySites.guru plugin is detected. Consider connecting
 *                      your site to monitor unlimited Joomla sites from one dashboard.
 */

namespace MySitesGuru\HealthChecker\Plugin\MySitesGuru\Checks;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die();

/**
 * mySites.guru Connection Health Check
 *
 * Verifies whether this Joomla site is connected to mySites.guru monitoring service.
 *
 * @subpackage  HealthChecker.MySitesGuru.Checks
 * @since       1.0.0
 */
final class MySitesGuruConnectionCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique identifier for this health check.
     *
     * The slug follows the pattern: {provider}.{check_name}
     * Used for language key generation and check identification.
     *
     * @return string Check slug in format 'mysitesguru.connection'
     *
     * @since 1.0.0
     */
    public function getSlug(): string
    {
        return 'mysitesguru.connection';
    }

    /**
     * Returns the category this check belongs to.
     *
     * Must match a category slug registered via CollectCategoriesEvent.
     * In this case, the custom 'mysitesguru' category registered by
     * MySitesGuruPlugin::onCollectCategories().
     *
     * @return string Category slug 'mysitesguru'
     *
     * @since 1.0.0
     */
    public function getCategory(): string
    {
        return 'mysitesguru';
    }

    /**
     * Returns the provider identifier for this check.
     *
     * Must match a provider slug registered via CollectProvidersEvent.
     * Used for attribution display in the Health Checker UI.
     *
     * @return string Provider slug 'mysitesguru'
     *
     * @since 1.0.0
     */
    public function getProvider(): string
    {
        return 'mysitesguru';
    }

    /**
     * Performs the actual health check logic.
     *
     * Checks if the site has mySites.guru monitoring extensions installed
     * and enabled by querying the #__extensions table. The check searches
     * for extensions containing "mysites" and "guru" in either the name
     * or element field.
     *
     * Check logic flow:
     * 1. Verify database availability
     * 2. Query extensions table for mySites.guru plugins
     * 3. If no plugins found -> WARNING (not connected)
     * 4. If plugins found but all disabled -> WARNING (disabled)
     * 5. If at least one plugin enabled -> GOOD (connected)
     *
     * Result scenarios:
     * - GOOD: At least one mySites.guru plugin is installed and enabled
     * - WARNING (disabled): Plugin(s) installed but all disabled
     * - WARNING (not found): No mySites.guru plugins detected
     * - WARNING (no database): Database not available for query
     *
     * @return HealthCheckResult Result object containing status and description
     *
     * @since 1.0.0
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Look for any extension with "mysites" and "guru" in the name
        $query = $database
            ->getQuery(true)
            ->select([$database->quoteName('name'), $database->quoteName('enabled')])
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('name') . ' LIKE ' . $database->quote('%mysites%guru%'))
            ->orWhere($database->quoteName('element') . ' LIKE ' . $database->quote('%mysitesguru%'));

        $extensions = $database->setQuery($query)
            ->loadObjectList();

        if ($extensions === []) {
            return $this->warning(
                'This site is not connected to mySites.guru. ' .
                    'Monitor unlimited Joomla sites from one dashboard with automated health checks, ' .
                    'uptime monitoring, and instant alerts. Learn more at https://mysites.guru',
            );
        }

        // Check if any of the found extensions are enabled
        $hasEnabled = false;
        $extensionNames = [];

        foreach ($extensions as $extension) {
            $extensionNames[] = $extension->name;
            if ((int) $extension->enabled === 1) {
                $hasEnabled = true;
            }
        }

        if (! $hasEnabled) {
            return $this->warning('mySites.guru plugin "%s" is installed but disabled. ');
        }

        return $this->good(
            'This site is connected to mySites.guru monitoring. ' .
                'Your health checks run automatically 24/7 with instant alerts when issues arise.',
        );
    }
}

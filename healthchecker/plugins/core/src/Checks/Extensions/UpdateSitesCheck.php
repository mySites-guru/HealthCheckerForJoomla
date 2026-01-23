<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Update Sites Health Check
 *
 * This check verifies that all extension update sites are enabled, ensuring
 * Joomla can check for and retrieve updates from all configured sources.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Update sites are the URLs Joomla uses to check for extension updates. If an
 * update site is disabled, you will not receive notifications about new versions
 * of that extension, including critical security updates. This could leave your
 * site vulnerable to known exploits that have been patched in newer versions.
 *
 * RESULT MEANINGS:
 *
 * GOOD: All configured update sites are enabled. You will receive update
 * notifications for all extensions.
 *
 * WARNING: One or more update sites are disabled. You may miss important
 * security updates for the affected extensions.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class UpdateSitesCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'extensions.update_sites'
     */
    public function getSlug(): string
    {
        return 'extensions.update_sites';
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
     * Performs the update sites health check.
     *
     * This method queries the #__update_sites table to count enabled and disabled
     * update sites. Update sites are the URLs that Joomla uses to check for
     * extension updates. Each installed extension can have one or more update sites.
     *
     * Disabled update sites mean Joomla won't check for updates from those sources,
     * potentially missing critical security patches.
     *
     * @return HealthCheckResult WARNING if any update sites are disabled, GOOD if all are enabled
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Count enabled update sites - these are actively checking for updates
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__update_sites'))
            ->where($database->quoteName('enabled') . ' = 1');

        $enabledSites = (int) $database->setQuery($query)
            ->loadResult();

        // Count disabled update sites - these will NOT check for updates
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__update_sites'))
            ->where($database->quoteName('enabled') . ' = 0');

        $disabledSites = (int) $database->setQuery($query)
            ->loadResult();

        // Any disabled sites mean potential missed updates
        if ($disabledSites > 0) {
            return $this->warning(
                sprintf('%d update site(s) disabled. You may miss important security updates.', $disabledSites),
            );
        }

        return $this->good(sprintf('%d update site(s) enabled.', $enabledSites));
    }
}

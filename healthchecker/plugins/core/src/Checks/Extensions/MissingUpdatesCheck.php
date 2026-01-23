<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Missing Updates Health Check
 *
 * This check counts the number of extension updates that are available but
 * have not yet been applied to your Joomla installation.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Pending updates often contain security patches, bug fixes, and new features.
 * Running outdated extensions increases your exposure to known vulnerabilities
 * and may cause compatibility issues with other components. Regularly applying
 * updates helps maintain site security and stability.
 *
 * RESULT MEANINGS:
 *
 * GOOD: All extensions are up to date. No pending updates are available.
 *
 * WARNING: One or more extension updates are available. Review and apply
 * updates, prioritizing those with security fixes. More than 5 pending updates
 * suggests updates have been deferred too long.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class MissingUpdatesCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in the format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'extensions.missing_updates';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug
     */
    public function getCategory(): string
    {
        return 'extensions';
    }

    /**
     * Perform the missing updates check.
     *
     * Counts the number of extension updates available but not yet applied.
     * The #__updates table is populated by Joomla's update checking system
     * and contains one row per available update for any extension.
     *
     * Extension ID 0 indicates system updates (handled separately), so we exclude those.
     * More than 5 pending updates indicates a backlog that should be addressed urgently.
     *
     * @return HealthCheckResult The result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Count pending extension updates
        // Extension ID != 0 excludes system/platform updates
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__updates'))
            ->where($database->quoteName('extension_id') . ' != 0');

        $pendingUpdates = (int) $database->setQuery($query)
            ->loadResult();

        // More than 5 updates indicates updates have been deferred too long
        if ($pendingUpdates > 5) {
            return $this->critical(
                sprintf(
                    '%d extension update(s) available - updates deferred too long. Apply security and critical updates immediately.',
                    $pendingUpdates,
                ),
            );
        }

        // Any pending updates should be addressed
        if ($pendingUpdates > 0) {
            return $this->warning(sprintf('%d extension update(s) available.', $pendingUpdates));
        }

        return $this->good('All extensions are up to date.');
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Joomla Core Version Health Check
 *
 * This check compares the currently installed Joomla version against the latest
 * available version from Joomla's update servers to identify if an update is available.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Keeping Joomla core up to date is critical for security, performance, and compatibility.
 * New versions often contain security patches that protect against known vulnerabilities,
 * bug fixes that improve stability, and new features that enhance functionality.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The installed Joomla version is the latest available version. No update is needed.
 *
 * WARNING: A newer version of Joomla is available. You should plan to update soon,
 * especially if the new version includes security fixes.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use Joomla\CMS\Version;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class JoomlaCoreVersionCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in the format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'extensions.joomla_core_version';
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
     * Perform the Joomla core version check.
     *
     * Compares the currently installed Joomla version against the latest available
     * version from Joomla's update servers. The update information is retrieved from
     * the #__updates table, which is populated by Joomla's update checking system.
     *
     * Extension ID 700 is always Joomla core itself in the updates table.
     *
     * @return HealthCheckResult The result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        // Get currently installed Joomla version
        $version = new Version();
        $currentVersion = $version->getShortVersion();

        $database = $this->requireDatabase();
        // Query #__updates table for latest Joomla version
        // Extension ID 700 is always Joomla core
        $query = $database->getQuery(true)
            ->select($database->quoteName('version'))
            ->from($database->quoteName('#__updates'))
            ->where($database->quoteName('extension_id') . ' = 700');

        $database->setQuery($query);
        $latestVersion = $database->loadResult();

        // Compare versions - if current is older than latest, warn
        if ($latestVersion && version_compare($currentVersion, $latestVersion, '<')) {
            return $this->warning(
                sprintf('Joomla %s is installed. Version %s is available.', $currentVersion, $latestVersion),
            );
        }

        return $this->good(sprintf('Joomla %s is the latest version.', $currentVersion));
    }
}

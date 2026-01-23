<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database SQL Mode Health Check
 *
 * This check examines the MySQL/MariaDB SQL mode settings to identify
 * potentially problematic modes that may cause compatibility issues.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * SQL modes control how MySQL handles invalid or missing data, as well as
 * SQL syntax validation. Certain modes can cause issues:
 * - ONLY_FULL_GROUP_BY: Breaks older extensions with non-standard GROUP BY
 * - STRICT_TRANS_TABLES: May reject data that older code expects to work
 * - NO_ZERO_DATE: Rejects '0000-00-00' dates that some extensions use
 *
 * RESULT MEANINGS:
 *
 * GOOD: The SQL mode is empty (permissive) or contains only non-problematic
 * modes. The current SQL mode string is displayed for reference.
 *
 * WARNING: ONLY_FULL_GROUP_BY is enabled, which may cause compatibility
 * issues with older extensions that use non-standard GROUP BY queries.
 * Some extensions may display errors or fail to function correctly.
 *
 * CRITICAL: The database connection is not available to check SQL mode.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class SqlModeCheck extends AbstractHealthCheck
{
    /**
     * List of SQL modes that may cause compatibility issues with Joomla extensions.
     *
     * @var array<string>
     */
    private const PROBLEMATIC_MODES = ['ONLY_FULL_GROUP_BY', 'STRICT_TRANS_TABLES', 'NO_ZERO_DATE'];

    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format "database.sql_mode"
     */
    public function getSlug(): string
    {
        return 'database.sql_mode';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug "database"
     */
    public function getCategory(): string
    {
        return 'database';
    }

    /**
     * Perform the SQL mode health check.
     *
     * Queries the database for the current SQL mode setting and examines it for
     * potentially problematic modes that may cause compatibility issues with Joomla.
     *
     * The check specifically looks for:
     * - ONLY_FULL_GROUP_BY: May break extensions with non-standard GROUP BY queries
     * - STRICT_TRANS_TABLES: May reject data that older code expects to work
     * - NO_ZERO_DATE: Rejects '0000-00-00' dates that some extensions use
     *
     * @return HealthCheckResult Critical if database unavailable, warning if ONLY_FULL_GROUP_BY
     *                           is enabled, good if SQL mode is empty or contains only safe modes
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Query the current SQL mode setting
        $query = 'SELECT @@sql_mode';
        $sqlMode = $database->setQuery($query)
            ->loadResult();

        // Parse the comma-separated SQL modes into an array
        $modes = $sqlMode ? explode(',', (string) $sqlMode) : [];

        // Find any problematic modes that are currently active
        $presentProblematic = array_intersect(self::PROBLEMATIC_MODES, $modes);

        // ONLY_FULL_GROUP_BY is the most likely to cause immediate issues
        if (in_array('ONLY_FULL_GROUP_BY', $presentProblematic, true)) {
            return $this->warning(
                'ONLY_FULL_GROUP_BY is enabled. Some older extensions may have compatibility issues.',
            );
        }

        if ($modes === []) {
            return $this->good('SQL mode is empty (permissive mode).');
        }

        return $this->good(sprintf('SQL mode: %s', $sqlMode));
    }
}

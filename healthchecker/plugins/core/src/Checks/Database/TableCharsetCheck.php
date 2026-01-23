<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Table Charset/Collation Health Check
 *
 * This check verifies that all Joomla database tables are using utf8mb4 collation,
 * which provides full Unicode support including emoji and special characters.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Table collation determines how text is stored and compared within each table.
 * Using utf8mb4 collation ensures:
 * - Full Unicode support for all content including emoji
 * - Consistent storage of international characters
 * - Proper string comparisons and sorting
 * - Prevention of data truncation for 4-byte characters
 *
 * RESULT MEANINGS:
 *
 * GOOD: All Joomla tables are using utf8mb4 collation. Your database can
 * properly store and compare all Unicode characters including emoji.
 *
 * WARNING: One or more tables are using a non-utf8mb4 collation. These tables
 * may not properly handle emoji or some special characters. Consider converting
 * them using ALTER TABLE ... CONVERT TO CHARACTER SET utf8mb4.
 *
 * CRITICAL: The database connection is not available to check table collations.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class TableCharsetCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format "database.table_charset"
     */
    public function getSlug(): string
    {
        return 'database.table_charset';
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
     * Perform the table charset/collation health check.
     *
     * Queries the information_schema to verify that all Joomla database tables
     * are using utf8mb4 collation. This ensures full Unicode support including
     * emoji and special 4-byte characters.
     *
     * The check:
     * 1. Gets the database name and table prefix from Joomla configuration
     * 2. Queries information_schema.TABLES for all tables with the Joomla prefix
     * 3. Identifies tables NOT using utf8mb4 collation
     * 4. Reports up to 5 problematic tables in the warning message
     *
     * @return HealthCheckResult Critical if database unavailable, warning if any tables
     *                           use non-utf8mb4 collation, good if all tables use utf8mb4
     */
    /**
     * Perform the Table Charset health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        $prefix = Factory::getApplication()->get('dbprefix');
        $dbName = Factory::getApplication()->get('db');

        // Query information_schema to find tables not using utf8mb4 collation
        // This checks the table-level collation, not column-level
        $query = 'SELECT TABLE_NAME, TABLE_COLLATION
                  FROM information_schema.TABLES
                  WHERE TABLE_SCHEMA = ' . $database->quote($dbName) . '
                  AND TABLE_NAME LIKE ' . $database->quote($prefix . '%') . "
                  AND TABLE_COLLATION NOT LIKE 'utf8mb4%'";

        $nonUtf8mb4Tables = $database->setQuery($query)
            ->loadObjectList();

        if ($nonUtf8mb4Tables !== []) {
            // Extract just the table names for display
            $tableNames = array_map(fn($t) => $t->TABLE_NAME, $nonUtf8mb4Tables);

            return $this->warning(
                sprintf(
                    '%d table(s) are not using utf8mb4 collation: %s',
                    count($nonUtf8mb4Tables),
                    implode(', ', array_slice($tableNames, 0, 5)) . (count($tableNames) > 5 ? '...' : ''),
                ),
            );
        }

        return $this->good('All Joomla tables are using utf8mb4 collation.');
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Table Engine Health Check
 *
 * This check verifies that all Joomla database tables are using the InnoDB
 * storage engine, which is the recommended engine for modern MySQL/MariaDB.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * InnoDB provides critical features that MyISAM and other engines lack:
 * - ACID-compliant transactions for data integrity
 * - Row-level locking for better concurrent performance
 * - Foreign key constraints for referential integrity
 * - Crash recovery and automatic corruption repair
 * - Better performance for read/write mixed workloads
 *
 * RESULT MEANINGS:
 *
 * GOOD: All Joomla tables are using the InnoDB engine. Your database has
 * full transaction support and optimal reliability.
 *
 * WARNING: One or more tables are using a non-InnoDB engine (likely MyISAM).
 * These tables lack transaction support and may be more susceptible to
 * corruption. Consider converting them to InnoDB using ALTER TABLE.
 *
 * CRITICAL: The database connection is not available to check table engines.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class TableEngineCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format "database.table_engine"
     */
    public function getSlug(): string
    {
        return 'database.table_engine';
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
     * Perform the table storage engine health check.
     *
     * Queries the information_schema to verify that all Joomla database tables
     * are using the InnoDB storage engine. InnoDB provides ACID-compliant transactions,
     * row-level locking, and crash recovery that MyISAM and other engines lack.
     *
     * The check:
     * 1. Gets the database name and table prefix from Joomla configuration
     * 2. Queries information_schema.TABLES for all tables with the Joomla prefix
     * 3. Identifies tables NOT using InnoDB engine (typically MyISAM)
     * 4. Reports up to 5 problematic tables in the warning message
     *
     * @return HealthCheckResult Critical if database unavailable, warning if any tables
     *                           use non-InnoDB engines, good if all tables use InnoDB
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        $prefix = Factory::getApplication()->get('dbprefix');
        $dbName = Factory::getApplication()->get('db');

        // Query information_schema to find tables not using InnoDB
        // Common alternatives are MyISAM (older) or Aria (MariaDB)
        $query =
            'SELECT TABLE_NAME, ENGINE
                  FROM information_schema.TABLES
                  WHERE TABLE_SCHEMA = ' .
            $database->quote($dbName) .
            'AND TABLE_NAME LIKE ' .
            $database->quote($prefix . '%') .
            "AND ENGINE != 'InnoDB'
                  AND ENGINE != 'MEMORY'";

        $nonInnoDbTables = $database->setQuery($query)
            ->loadObjectList();

        if ($nonInnoDbTables !== []) {
            // Extract just the table names for display
            $tableNames = array_map(fn($t) => $t->TABLE_NAME, $nonInnoDbTables);

            return $this->warning(
                sprintf(
                    '%d table(s) are not using InnoDB/MEMORY: %s',
                    count($nonInnoDbTables),
                    implode(', ', array_slice($tableNames, 0, 5)) .
                        (count($tableNames) > 5 ? '...' : ''),
                ),
            );
        }

        return $this->good('All Joomla tables are using InnoDB/MEMORY engine.');
    }
}

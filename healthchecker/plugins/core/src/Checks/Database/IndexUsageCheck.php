<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Index Usage Health Check
 *
 * This check examines all Joomla database tables to ensure they have primary
 * keys and appropriate indexes for optimal query performance.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Database indexes are critical for performance:
 * - Primary keys ensure row uniqueness and enable efficient lookups
 * - Indexes speed up WHERE clauses, JOINs, and ORDER BY operations
 * - Missing indexes cause full table scans, slowing down the entire site
 * - Tables without primary keys may have replication issues
 *
 * RESULT MEANINGS:
 *
 * GOOD: All Joomla tables have primary keys and at least basic indexes.
 * Your database is properly structured for efficient queries.
 *
 * WARNING (missing primary key): One or more tables lack a primary key.
 * This impacts performance, prevents efficient updates/deletes, and may
 * cause issues with database replication. Add primary keys to these tables.
 *
 * WARNING (no indexes): One or more tables have no indexes at all.
 * Queries against these tables will perform full table scans.
 *
 * CRITICAL: The database connection is not available to check indexes.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class IndexUsageCheck extends AbstractHealthCheck
{
    /**
     * Joomla core tables that are intentionally designed without primary keys.
     * These are excluded from the primary key check.
     *
     * @var list<string>
     */
    private const TABLES_WITHOUT_PRIMARY_KEY_BY_DESIGN = [
        'contentitem_tag_map',
        'fields_values',
        'finder_terms_common',
        'finder_tokens',
        'finder_tokens_aggregate',
        'messages_cfg',
        'user_profiles',
    ];

    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'database.index_usage'
     */
    public function getSlug(): string
    {
        return 'database.index_usage';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'database'
     */
    public function getCategory(): string
    {
        return 'database';
    }

    /**
     * Perform the database index usage health check.
     *
     * Examines all Joomla database tables to ensure they have primary
     * keys and appropriate indexes for optimal query performance.
     *
     * Check logic:
     * 1. Get all tables with the Joomla prefix
     * 2. For each table, query SHOW INDEX to get index information
     * 3. Check for PRIMARY key existence
     * 4. Check for other indexes (non-primary)
     * 5. If tables missing primary keys: WARNING - list affected tables
     * 6. If tables with no indexes at all: WARNING - list affected tables
     * 7. If all tables have primary keys + indexes: GOOD
     *
     * Primary keys ensure row uniqueness and enable efficient lookups.
     * Indexes speed up WHERE clauses, JOINs, and ORDER BY operations.
     *
     * @return HealthCheckResult The result with appropriate status and message
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        $prefix = Factory::getApplication()->get('dbprefix');
        Factory::getApplication()->get('db');

        // Get all tables with the Joomla prefix
        $query = 'SHOW TABLES LIKE ' . $database->quote($prefix . '%');
        $tables = $database->setQuery($query)
            ->loadColumn();

        $tablesWithoutPrimaryKey = [];
        $tablesWithoutIndexes = [];

        // Check each table for primary key and indexes
        foreach ($tables as $table) {
            // Query index information for this table
            $indexQuery = sprintf('SHOW INDEX FROM %s', $database->quoteName($table));

            try {
                $indexes = $database->setQuery($indexQuery)
                    ->loadObjectList();
            } catch (\Exception) {
                // Skip tables we can't query (permissions, temporary tables, etc.)
                continue;
            }

            $hasPrimaryKey = false;
            $hasOtherIndexes = false;

            // Check what types of indexes exist
            foreach ($indexes as $index) {
                if ($index->Key_name === 'PRIMARY') {
                    $hasPrimaryKey = true;
                } else {
                    $hasOtherIndexes = true;
                }
            }

            // Track tables missing primary key (skip tables designed without one)
            if (! $hasPrimaryKey && ! $this->isTableWithoutPrimaryKeyByDesign($table, $prefix)) {
                $tablesWithoutPrimaryKey[] = $table;
            }

            // Track tables with no indexes at all (neither primary nor other)
            if (! $hasPrimaryKey && ! $hasOtherIndexes) {
                $tablesWithoutIndexes[] = $table;
            }
        }

        // Report tables missing primary keys (most serious issue)
        if ($tablesWithoutPrimaryKey !== []) {
            return $this->warning(
                sprintf(
                    '%d table(s) missing primary key: %s. This may impact performance and data integrity.',
                    count($tablesWithoutPrimaryKey),
                    implode(', ', array_slice($tablesWithoutPrimaryKey, 0, 5)) . (count(
                        $tablesWithoutPrimaryKey,
                    ) > 5 ? '...' : ''),
                ),
            );
        }

        // Report tables with absolutely no indexes
        if ($tablesWithoutIndexes !== []) {
            return $this->warning(
                sprintf(
                    '%d table(s) have no indexes at all: %s',
                    count($tablesWithoutIndexes),
                    implode(', ', array_slice($tablesWithoutIndexes, 0, 5)) . (count(
                        $tablesWithoutIndexes,
                    ) > 5 ? '...' : ''),
                ),
            );
        }

        // All tables have proper indexing
        return $this->good(sprintf('All %d tables have primary keys and indexes.', count($tables)));
    }

    /**
     * Check if a table is one of the Joomla core tables designed without a primary key.
     *
     * @param string $tableName The full table name (with prefix)
     * @param string $prefix    The Joomla database prefix
     *
     * @return bool True if the table is designed without a primary key
     */
    private function isTableWithoutPrimaryKeyByDesign(string $tableName, string $prefix): bool
    {
        // Remove the prefix to get the base table name
        $baseTableName = str_starts_with($tableName, $prefix)
            ? substr($tableName, strlen($prefix))
            : $tableName;

        return in_array($baseTableName, self::TABLES_WITHOUT_PRIMARY_KEY_BY_DESIGN, true);
    }
}

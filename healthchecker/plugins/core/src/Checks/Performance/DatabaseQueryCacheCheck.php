<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Query Cache Health Check
 *
 * This check examines the MySQL/MariaDB query cache configuration. Note that
 * query cache was deprecated in MySQL 5.7 and removed entirely in MySQL 8.0.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * For MySQL < 8.0 and MariaDB, query cache can improve performance by storing
 * the results of SELECT queries in memory. However, for MySQL 8.0+, query cache
 * no longer exists and application-level caching (like Joomla's cache system)
 * should be used instead.
 *
 * RESULT MEANINGS:
 *
 * GOOD: For MySQL 8.0+: Query cache is not available (expected behavior), use
 * application-level caching. For MySQL < 8.0 or MariaDB: Query cache is properly
 * configured with memory allocated.
 *
 * WARNING: For MySQL < 8.0 or MariaDB: Query cache is disabled or has no memory
 * allocated. Consider enabling it for read-heavy workloads, or use Joomla's
 * built-in caching system instead.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class DatabaseQueryCacheCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'performance.database_query_cache'
     */
    public function getSlug(): string
    {
        return 'performance.database_query_cache';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category identifier 'performance'
     */
    public function getCategory(): string
    {
        return 'performance';
    }

    /**
     * Perform the database query cache health check.
     *
     * This method examines MySQL/MariaDB query cache configuration. Query cache
     * stores SELECT query results in memory for faster retrieval on subsequent
     * identical queries. However, this feature has version-specific considerations:
     *
     * - MySQL 5.7: Query cache deprecated (use application-level caching)
     * - MySQL 8.0+: Query cache removed entirely (not available)
     * - MariaDB: Query cache still available and can improve read-heavy workloads
     *
     * The check performs these steps:
     * 1. Detects database version and type (MySQL vs MariaDB)
     * 2. For MySQL 8.0+: Reports cache unavailable (expected)
     * 3. For MySQL < 8.0 or MariaDB: Examines query_cache% variables
     * 4. Checks query_cache_type (ON/OFF) and query_cache_size (memory allocation)
     *
     * Returns:
     * - GOOD: MySQL 8.0+ (cache not applicable), or cache properly configured
     * - WARNING: Cache disabled, size is 0, or variables unavailable
     *
     * @return HealthCheckResult The result indicating query cache configuration status
     */
    /**
     * Perform the Database Query Cache health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Detect database version and type for version-specific handling
        $version = $database->getVersion();
        $isMariaDb = stripos($version, 'mariadb') !== false;
        $numericVersion = preg_replace('/[^0-9.]/', '', $version);

        if ($numericVersion === null || $numericVersion === '') {
            return $this->warning('Unable to determine database version.');
        }

        // MySQL 8.0+ removed query cache entirely - this is expected behavior
        if (! $isMariaDb && version_compare($numericVersion, '8.0', '>=')) {
            return $this->good(
                'MySQL 8.0+ detected. Query cache was deprecated and removed. Use application-level caching instead.',
            );
        }

        // For MySQL < 8.0 or MariaDB, query the query_cache% server variables
        $query = 'SHOW VARIABLES LIKE ' . $database->quote('query_cache%');
        $results = $database->setQuery($query)
            ->loadAssocList('Variable_name', 'Value');

        if ($results === []) {
            return $this->good('Query cache variables not available. This feature may not be supported.');
        }

        // Extract query cache configuration values
        $queryCacheType = $results['query_cache_type'] ?? 'OFF';
        $queryCacheSize = (int) ($results['query_cache_size'] ?? 0);

        // Check if query cache is disabled (type = OFF or 0)
        if ($queryCacheType === 'OFF' || $queryCacheType === '0') {
            // MariaDB still supports query cache - could be enabled
            if ($isMariaDb) {
                return $this->warning('Query cache is disabled. Consider enabling it for read-heavy workloads.');
            }

            // MySQL 5.7 - query cache deprecated, recommend application caching
            return $this->good(
                'Query cache is disabled. For MySQL 5.7 consider using application-level caching as query cache is deprecated.',
            );
        }

        // Query cache type is ON but no memory allocated - misconfiguration
        if ($queryCacheSize === 0) {
            return $this->warning(
                'Query cache type is enabled but cache size is 0. Increase query_cache_size to enable caching.',
            );
        }

        // Query cache is properly configured - convert bytes to MB for readability
        $sizeInMb = round($queryCacheSize / 1024 / 1024, 2);

        return $this->good(sprintf('Query cache is enabled with %s MB allocated.', $sizeInMb));
    }
}

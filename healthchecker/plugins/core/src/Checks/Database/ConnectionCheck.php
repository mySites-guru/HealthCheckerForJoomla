<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Connection Health Check
 *
 * This check verifies that Joomla can successfully connect to and query the database.
 * It performs a simple SELECT statement to confirm the connection is functional.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The database is the foundation of every Joomla site, storing all content, users,
 * configurations, and extension data. If the database connection fails, the entire
 * site becomes non-functional. Early detection of connection issues allows for
 * immediate intervention before users experience downtime.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The database connection is working correctly. Joomla can execute queries
 * and retrieve data without any issues.
 *
 * WARNING: Not applicable for this check - connection either works or fails.
 *
 * CRITICAL: The database connection has failed. This could be due to:
 * - Incorrect database credentials in configuration.php
 * - Database server is down or unreachable
 * - Network connectivity issues between web server and database
 * - Database user lacks permission to connect
 * - Maximum connection limit reached on the database server
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ConnectionCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'database.connection'
     */
    public function getSlug(): string
    {
        return 'database.connection';
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
     * Perform the Connection health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        try {
            $database->setQuery('SELECT 1');
            $database->execute();

            return $this->good('Database connection is working correctly.');
        } catch (\Exception $exception) {
            return $this->critical(sprintf('Database connection failed: %s', $exception->getMessage()));
        }
    }
}

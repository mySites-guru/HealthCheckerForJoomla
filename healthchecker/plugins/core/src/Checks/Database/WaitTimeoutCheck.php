<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Wait Timeout Health Check
 *
 * This check examines the MySQL/MariaDB wait_timeout setting to ensure it
 * is appropriate for Joomla operations (not too short or excessively long).
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The wait_timeout setting controls how long the database server waits for
 * activity on a connection before closing it:
 * - Too short (< 30s): Long-running operations may fail unexpectedly
 * - Too long (> 8 hours): Idle connections waste server resources
 * Finding the right balance ensures reliable operation without resource waste.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The wait_timeout is between 30 seconds and 8 hours, which provides
 * a good balance between reliability and resource efficiency.
 *
 * WARNING (too low): The wait_timeout is below 30 seconds. Long-running
 * operations like large imports, backups, or complex queries may fail with
 * "MySQL server has gone away" errors.
 *
 * WARNING (too high): The wait_timeout exceeds 8 hours. While this won't
 * cause failures, it wastes database server resources by keeping idle
 * connections open. Consider reducing this value.
 *
 * CRITICAL: The database connection is not available to check wait_timeout.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class WaitTimeoutCheck extends AbstractHealthCheck
{
    /**
     * Minimum acceptable wait_timeout value in seconds.
     *
     * Below this value, long-running operations like imports or backups
     * may fail with "MySQL server has gone away" errors.
     *
     * @var int
     */
    private const MINIMUM_SECONDS = 30;

    /**
     * Maximum recommended wait_timeout value in seconds (8 hours).
     *
     * Above this value, idle connections waste database server resources.
     *
     * @var int
     */
    private const MAXIMUM_SECONDS = 28800;

    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format "database.wait_timeout"
     */
    public function getSlug(): string
    {
        return 'database.wait_timeout';
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
     * Perform the database wait_timeout health check.
     *
     * Examines the MySQL/MariaDB wait_timeout setting to ensure it is appropriate
     * for Joomla operations. The wait_timeout setting controls how long the database
     * server waits for activity on a connection before closing it.
     *
     * Issues detected:
     * - Too low (< 30s): Long operations may fail with connection errors
     * - Too high (> 8h): Idle connections waste database server resources
     *
     * The check queries the wait_timeout system variable using SHOW VARIABLES,
     * which returns an object with Variable_name and Value properties.
     *
     * @return HealthCheckResult Critical if database unavailable, warning if timeout
     *                           is below 30 seconds or above 8 hours, good if timeout
     *                           is in the recommended range
     */
    /**
     * Perform the Wait Timeout health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Query the wait_timeout system variable
        // Returns object with Variable_name='wait_timeout' and Value='<seconds>'
        $query = "SHOW VARIABLES LIKE 'wait_timeout'";
        $result = $database->setQuery($query)
            ->loadObject();

        $waitTimeout = (int) ($result->Value ?? 0);

        // Extremely low timeout can cause "MySQL server has gone away" errors
        if ($waitTimeout < self::MINIMUM_SECONDS) {
            return $this->warning(
                sprintf('wait_timeout (%ds) is very low. Long operations may fail.', $waitTimeout),
            );
        }

        // Extremely high timeout wastes resources on idle connections
        if ($waitTimeout > self::MAXIMUM_SECONDS) {
            return $this->warning(
                sprintf('wait_timeout (%ds) is very high. Consider reducing to conserve resources.', $waitTimeout),
            );
        }

        return $this->good(sprintf('wait_timeout is %d seconds.', $waitTimeout));
    }
}

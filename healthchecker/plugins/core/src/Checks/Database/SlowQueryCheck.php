<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Slow Query Log Health Check
 *
 * This check examines whether the MySQL/MariaDB slow query log is enabled,
 * which can impact performance when left on in production environments.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The slow query log, while useful for performance troubleshooting, has overhead:
 * - Every query must be checked against the long_query_time threshold
 * - Slow queries are written to disk, adding I/O overhead
 * - In production, this can impact overall database performance
 * - Should only be enabled temporarily during active debugging sessions
 * It's a diagnostic tool, not something to leave permanently enabled.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The slow query log is disabled. This is the optimal state for
 * production environments - no overhead from query timing or logging.
 *
 * WARNING: The slow query log is enabled while Joomla debug mode is also
 * enabled. This is acceptable during active debugging sessions but should
 * be disabled once troubleshooting is complete.
 *
 * CRITICAL: The slow query log is enabled in production (debug mode off).
 * This adds unnecessary overhead to every database query. Disable it
 * unless you are actively troubleshooting performance issues.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class SlowQueryCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'database.slow_query'
     */
    public function getSlug(): string
    {
        return 'database.slow_query';
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
     * Perform the slow query log health check.
     *
     * Examines whether the MySQL/MariaDB slow query log is enabled,
     * which can add overhead in production environments.
     *
     * Check logic:
     * 1. Query @@slow_query_log to check if logging is enabled
     * 2. Get @@long_query_time threshold setting
     * 3. Check if Joomla debug mode is enabled
     * 4. If disabled: GOOD - no overhead in production
     * 5. If enabled + debug mode on: WARNING - acceptable during debugging
     * 6. If enabled + debug mode off: CRITICAL - unnecessary production overhead
     *
     * @return HealthCheckResult The result with appropriate status and message
     */
    /**
     * Perform the Slow Query health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        try {
            // Query slow query log configuration variables
            $slowQueryEnabled = $database->setQuery('SELECT @@slow_query_log')
                ->loadResult();
            $longQueryTime = $database->setQuery('SELECT @@long_query_time')
                ->loadResult();

            // Check if slow query logging is enabled (can be '1', 1, or 'ON')
            $isEnabled = $slowQueryEnabled === '1'
                || $slowQueryEnabled === 1
                || strtoupper((string) $slowQueryEnabled) === 'ON';

            // Slow query log is disabled - optimal for production
            if (! $isEnabled) {
                return $this->good('Slow query log is disabled. No overhead from query timing or logging.');
            }

            // Slow query log is enabled - check if debug mode is also on
            $debugModeEnabled = Factory::getApplication()->get('debug', false);

            // Attempt to get cumulative slow query count since server start
            $slowQueries = null;
            try {
                $result = $database->setQuery("SHOW GLOBAL STATUS LIKE 'Slow_queries'")
                    ->loadObject();
                if ($result !== null) {
                    $slowQueries = (int) $result->Value;
                }
            } catch (\Exception) {
                // Unable to get slow query count, continue without it
            }

            $message = sprintf('Slow query log is enabled (threshold: %s seconds).', $longQueryTime);

            // Append slow query count if available
            if ($slowQueries !== null && $slowQueries > 0) {
                $message .= sprintf(' %d slow queries recorded since server start.', $slowQueries);
            }

            // If debug mode is also enabled, this is acceptable for debugging
            if ($debugModeEnabled) {
                return $this->warning(
                    $message . ' This is acceptable during active debugging but should be disabled in production.',
                );
            }

            // Slow query log enabled in production - critical
            return $this->critical(
                $message . ' Disable slow query logging in production to reduce database overhead.',
            );
        } catch (\Exception $exception) {
            return $this->warning('Unable to check slow query log status: ' . $exception->getMessage());
        }
    }
}

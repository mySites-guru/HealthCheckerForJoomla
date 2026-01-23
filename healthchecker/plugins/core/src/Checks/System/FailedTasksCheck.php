<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Failed Tasks Health Check
 *
 * This check queries the Joomla scheduler to identify tasks that have failed on their
 * most recent execution. It counts tasks with a non-zero last_exit_code, which indicates
 * the task encountered an error or exception during execution.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Failed scheduled tasks can indicate configuration problems, missing dependencies,
 * database issues, or code errors in extensions. Unlike overdue tasks (which indicate
 * the scheduler isn't running), failed tasks mean the scheduler ran but the task itself
 * encountered problems that need investigation.
 *
 * RESULT MEANINGS:
 *
 * GOOD: All scheduled tasks completed successfully on their last run. Background
 * operations are functioning correctly.
 *
 * WARNING: One or more tasks have failed. This could be a transient error (network
 * timeout, temporary database issue) or a persistent problem. Check System -> Scheduled
 * Tasks and review the task logs to identify the cause. More than 5 failures suggests
 * a systemic issue requiring prompt attention.
 *
 * CRITICAL: This check does not produce critical results, as failed tasks can often
 * be resolved by re-running or fixing configuration.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class FailedTasksCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.failed_tasks'
     */
    public function getSlug(): string
    {
        return 'system.failed_tasks';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'system'
     */
    public function getCategory(): string
    {
        return 'system';
    }

    /**
     * Perform the failed tasks health check.
     *
     * Queries Joomla's scheduler table to count tasks with non-zero last_exit_code,
     * indicating the task encountered an error during its most recent execution.
     * Unlike overdue tasks (scheduler not running), failed tasks mean the scheduler
     * ran but the task itself had problems.
     *
     * Thresholds:
     * - 0 tasks: Good - all tasks completing successfully
     * - 1-5 tasks: Warning - some failures, investigate logs
     * - >5 tasks: Warning (elevated) - multiple failures suggesting systemic issue
     *
     * @return HealthCheckResult Status based on number of failed tasks
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Verify database connection is available
        // Query for tasks where last_exit_code is non-zero (indicating failure)
        // Exit code of 0 means success, any other value indicates an error occurred
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__scheduler_tasks'))
            ->where($database->quoteName('last_exit_code') . ' != 0')
            ->where($database->quoteName('last_exit_code') . ' IS NOT NULL');  // Exclude never-run tasks

        $failedCount = (int) $database->setQuery($query)
            ->loadResult();

        // More than 5 failed tasks suggests a systemic problem
        if ($failedCount > 5) {
            return $this->warning(
                sprintf('%d scheduled tasks have failed recently. Review the task logs.', $failedCount),
            );
        }

        // Any failed tasks warrant investigation
        if ($failedCount > 0) {
            return $this->warning(
                sprintf('%d scheduled task(s) have failed. Check the scheduler logs for details.', $failedCount),
            );
        }

        return $this->good('All scheduled tasks are running successfully.');
    }
}

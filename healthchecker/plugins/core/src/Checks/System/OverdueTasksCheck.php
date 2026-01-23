<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Overdue Tasks Health Check
 *
 * This check queries the Joomla scheduler to identify tasks that should have run but
 * have not. It counts enabled tasks where the next_execution timestamp is in the past,
 * indicating the task scheduler is not running or is significantly delayed.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Joomla's Task Scheduler handles critical background operations like sending email
 * notifications, cleaning up sessions, updating extensions, and running privacy consent
 * expiration. If the scheduler stops running, these operations silently fail, potentially
 * causing data buildup, missed notifications, and compliance issues.
 *
 * RESULT MEANINGS:
 *
 * GOOD: No scheduled tasks are overdue. The task scheduler is running correctly and
 * processing tasks on schedule.
 *
 * WARNING: 1-10 tasks are overdue. The cron job triggering the scheduler may be
 * misconfigured, running infrequently, or recently stopped. Review System -> Scheduled
 * Tasks and verify your server's cron configuration.
 *
 * CRITICAL: More than 10 tasks are overdue. The task scheduler has likely stopped
 * running entirely. This requires immediate attention as critical background operations
 * are not being performed.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class OverdueTasksCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.overdue_tasks'
     */
    public function getSlug(): string
    {
        return 'system.overdue_tasks';
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
     * Perform the overdue tasks health check.
     *
     * Queries Joomla's scheduler table to count enabled tasks where the next_execution
     * timestamp is in the past. This indicates tasks that should have run but haven't,
     * suggesting the task scheduler cron job is not running or is significantly delayed.
     *
     * Thresholds:
     * - 0 tasks: Good - scheduler is running correctly
     * - 1-10 tasks: Warning - scheduler may be delayed or misconfigured
     * - >10 tasks: Critical - scheduler likely stopped entirely
     *
     * @return HealthCheckResult Status based on number of overdue tasks
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Verify database connection is available
        // Query for enabled tasks (state=1) where next_execution is in the past
        // These are tasks that should have run but haven't yet
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__scheduler_tasks'))
            ->where($database->quoteName('state') . ' = 1')  // Only enabled tasks
            ->where($database->quoteName('next_execution') . ' < NOW()');  // Execution time has passed

        $overdueCount = (int) $database->setQuery($query)
            ->loadResult();

        // More than 10 overdue tasks indicates scheduler has likely stopped completely
        if ($overdueCount > 10) {
            return $this->critical(
                sprintf('%d scheduled tasks are overdue. The task scheduler may not be running.', $overdueCount),
            );
        }

        // 1-10 overdue tasks suggests scheduler is delayed or running infrequently
        if ($overdueCount > 0) {
            return $this->warning(
                sprintf('%d scheduled task(s) are overdue. Check your cron configuration.', $overdueCount),
            );
        }

        return $this->good('No overdue scheduled tasks.');
    }
}

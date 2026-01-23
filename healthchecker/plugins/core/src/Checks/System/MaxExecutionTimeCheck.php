<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Max Execution Time Health Check
 *
 * This check verifies that PHP scripts have sufficient time to complete operations.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Many Joomla operations are time-intensive: updates, backups, imports, search indexing.
 * If max_execution_time is too low:
 * - Extension installations may fail mid-process
 * - Database operations may timeout leaving data corrupted
 * - Backups and exports will be incomplete
 * - Scheduled tasks may not finish
 *
 * RESULT MEANINGS:
 *
 * GOOD: Execution time is 60 seconds or more (or unlimited).
 *       Scripts have adequate time for complex operations like updates and backups.
 *
 * WARNING: Execution time is between 30-60 seconds.
 *          Basic operations work but complex tasks like full backups
 *          or large imports may timeout.
 *
 * CRITICAL: Execution time is below 30 seconds.
 *           Many administrative operations will fail. Updates, installations,
 *           and backups are at high risk of timing out.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class MaxExecutionTimeCheck extends AbstractHealthCheck
{
    /**
     * Minimum acceptable execution time in seconds.
     */
    private const MINIMUM_SECONDS = 30;

    /**
     * Recommended execution time in seconds.
     */
    private const RECOMMENDED_SECONDS = 60;

    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'system.max_execution_time'
     */
    public function getSlug(): string
    {
        return 'system.max_execution_time';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category identifier 'system'
     */
    public function getCategory(): string
    {
        return 'system';
    }

    /**
     * Perform the max execution time check.
     *
     * Verifies that PHP scripts have sufficient time to complete complex operations
     * such as extension installations, updates, backups, and database operations.
     * A value of 0 means unlimited execution time.
     *
     * @return HealthCheckResult Critical if below 30s, Warning if below 60s, Good otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $maxExecutionTime = (int) ini_get('max_execution_time');

        if ($maxExecutionTime === 0) {
            return $this->good('Max execution time is unlimited.');
        }

        if ($maxExecutionTime < self::MINIMUM_SECONDS) {
            return $this->critical(
                sprintf(
                    'Max execution time (%ds) is below the minimum required %ds.',
                    $maxExecutionTime,
                    self::MINIMUM_SECONDS,
                ),
            );
        }

        if ($maxExecutionTime < self::RECOMMENDED_SECONDS) {
            return $this->warning(
                sprintf(
                    'Max execution time (%ds) is below the recommended %ds.',
                    $maxExecutionTime,
                    self::RECOMMENDED_SECONDS,
                ),
            );
        }

        return $this->good(sprintf('Max execution time (%ds) meets requirements.', $maxExecutionTime));
    }
}

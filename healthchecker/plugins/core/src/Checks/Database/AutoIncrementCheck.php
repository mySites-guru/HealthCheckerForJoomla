<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Auto-Increment Health Check
 *
 * This check monitors auto-increment column values to detect when they are
 * approaching their maximum limit (80% of INT maximum: 2,147,483,647).
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Auto-increment columns are used for primary keys in most database tables.
 * When they reach their maximum value:
 * - New records cannot be inserted
 * - The site may crash or display errors
 * - Data integrity is compromised
 * High-traffic sites or tables with frequent inserts/deletes may exhaust
 * their auto-increment space faster than expected.
 *
 * RESULT MEANINGS:
 *
 * GOOD: All auto-increment values have sufficient headroom. No tables are
 * approaching their maximum value limits.
 *
 * WARNING: One or more tables have auto-increment values exceeding 80% of
 * the INT maximum. Consider archiving old data, resetting the counter after
 * cleanup, or converting the column to BIGINT for more capacity.
 *
 * CRITICAL: The database connection is not available to check auto-increment values.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class AutoIncrementCheck extends AbstractHealthCheck
{
    private const WARNING_THRESHOLD = 0.8; // 80% of max value

    private const INT_MAX = 2147483647;

    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'database.auto_increment'
     */
    public function getSlug(): string
    {
        return 'database.auto_increment';
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
     * Perform the Auto Increment health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        $prefix = Factory::getApplication()->get('dbprefix');

        $query = 'SHOW TABLE STATUS LIKE ' . $database->quote($prefix . '%');
        $tables = $database->setQuery($query)
            ->loadObjectList();

        $warnings = [];

        foreach ($tables as $table) {
            if ($table->Auto_increment === null) {
                continue;
            }

            $autoIncrement = (int) $table->Auto_increment;
            $threshold = self::INT_MAX * self::WARNING_THRESHOLD;

            if ($autoIncrement > $threshold) {
                $percent = round(($autoIncrement / self::INT_MAX) * 100, 1);
                $warnings[] = sprintf('%s (%s%%)', $table->Name, $percent);
            }
        }

        if ($warnings !== []) {
            return $this->warning(
                sprintf(
                    'Auto-increment values are high in: %s. Consider archiving old data.',
                    implode(', ', $warnings),
                ),
            );
        }

        return $this->good('Auto-increment values have sufficient headroom.');
    }
}

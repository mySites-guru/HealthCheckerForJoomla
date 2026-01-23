<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Inactive Users Health Check
 *
 * This check identifies active (non-blocked) user accounts that have not logged
 * in for over 365 days. It examines the lastvisitDate field to determine user
 * activity, including users who have never logged in.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Inactive user accounts represent a security risk as they may have been forgotten
 * but still retain access permissions. These dormant accounts could be targets for
 * credential stuffing attacks, especially if users reused passwords that have since
 * been compromised in data breaches. Regular review of inactive accounts helps
 * maintain a clean user base and reduces potential attack vectors. Additionally,
 * inactive accounts may indicate abandoned registrations or spam signups that
 * should be cleaned up.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Fewer than 100 users are inactive, or all active users have logged in
 *       within the past year. This is normal for most sites and indicates healthy
 *       user engagement.
 *
 * WARNING: More than 100 user accounts have not logged in for over 365 days.
 *          This high number suggests a need to review and potentially clean up
 *          old accounts. Consider blocking or removing accounts that are no
 *          longer needed.
 *
 * Note: This check does not produce CRITICAL results as inactive accounts alone
 * do not represent an immediate threat, but should be addressed for good hygiene.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Users;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class InactiveUsersCheck extends AbstractHealthCheck
{
    /**
     * Number of days after which a user is considered inactive if they haven't logged in.
     *
     * @var int
     */
    private const INACTIVE_DAYS = 365;

    /**
     * Returns the unique identifier for this check.
     *
     * @return string The check slug in the format 'users.inactive_users'
     */
    public function getSlug(): string
    {
        return 'users.inactive_users';
    }

    /**
     * Returns the category this check belongs to.
     *
     * @return string The category slug 'users'
     */
    public function getCategory(): string
    {
        return 'users';
    }

    /**
     * Performs the inactive users health check.
     *
     * Identifies active (non-blocked) user accounts that have not logged in for over
     * 365 days by examining the lastvisitDate field. Dormant accounts represent a
     * security risk and should be reviewed for potential cleanup.
     *
     * @return HealthCheckResult WARNING if >100 inactive users, GOOD otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Calculate cutoff date (365 days ago)
        $cutoffDate = (new \DateTime())->modify('-' . self::INACTIVE_DAYS . ' days')->format('Y-m-d H:i:s');

        // Find active users who haven't logged in since cutoff date
        // Include users with NULL or zero dates (never logged in)
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__users'))
            ->where($database->quoteName('block') . ' = 0') // Only check active users
            ->where('(' . $database->quoteName('lastvisitDate') . ' < ' . $database->quote($cutoffDate) .
                    ' OR ' . $database->quoteName('lastvisitDate') . ' IS NULL' .
                    ' OR ' . $database->quoteName('lastvisitDate') . ' = ' . $database->quote(
                        '0000-00-00 00:00:00',
                    ) . ')');

        $inactiveCount = (int) $database->setQuery($query)
            ->loadResult();

        // Warning threshold: More than 100 inactive users needs attention
        if ($inactiveCount > 100) {
            return $this->warning(
                sprintf(
                    '%d users have not logged in for over %d days. Consider reviewing inactive accounts.',
                    $inactiveCount,
                    self::INACTIVE_DAYS,
                ),
            );
        }

        // Some inactive users exist but within acceptable range
        if ($inactiveCount > 0) {
            return $this->good(
                sprintf('%d user(s) inactive for over %d days.', $inactiveCount, self::INACTIVE_DAYS),
            );
        }

        return $this->good('All active users have logged in recently.');
    }
}

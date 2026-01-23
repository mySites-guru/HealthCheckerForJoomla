<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Password Expiry Health Check
 *
 * This check identifies active users whose passwords have not been changed in
 * over 365 days. It examines the lastResetTime field which tracks when each
 * user last reset their password.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Stale passwords increase security risk over time. Passwords may be exposed
 * through data breaches on other sites (where users reused passwords), shoulder
 * surfing, phishing, or simply being shared and forgotten. Regular password
 * rotation ensures that any compromised credentials have a limited window of
 * usefulness to attackers. While modern guidance de-emphasizes forced rotation
 * for complexity's sake, annual review of password age helps identify accounts
 * with potentially compromised or weak legacy passwords.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Fewer than 75% of users have passwords older than 365 days, or all users
 *       have recently updated their passwords. The specific count is reported for
 *       awareness. Some older passwords are normal and acceptable.
 *
 * WARNING: More than 75% of active users have not changed their password in over
 *          a year. This high percentage suggests a need to implement or encourage
 *          password hygiene. Consider prompting users to update passwords or
 *          implementing a password expiry policy.
 *
 * Note: This check does not produce CRITICAL results as password age alone is not
 * an immediate threat, but a long-term risk factor to address through policy.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Users;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class PasswordExpiryCheck extends AbstractHealthCheck
{
    /**
     * Number of days after which a password is considered expired.
     *
     * @var int
     */
    private const PASSWORD_EXPIRY_DAYS = 365;

    /**
     * Returns the unique identifier for this check.
     *
     * @return string The check slug in the format 'users.password_expiry'
     */
    public function getSlug(): string
    {
        return 'users.password_expiry';
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
     * Performs the password expiry health check.
     *
     * Identifies active users whose passwords have not been changed in over 365 days
     * by examining the lastResetTime field. Calculates the percentage of users with
     * expired passwords and returns WARNING if more than 25% have stale passwords.
     *
     * @return HealthCheckResult WARNING if >25% of users have expired passwords, GOOD otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Calculate cutoff date (365 days ago)
        $cutoffDate = (new \DateTime())->modify('-' . self::PASSWORD_EXPIRY_DAYS . ' days')->format('Y-m-d H:i:s');

        // Check users where lastResetTime is older than the cutoff date
        // lastResetTime tracks when password was last reset
        // Include NULL and zero dates as expired (never changed password)
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__users'))
            ->where($database->quoteName('block') . ' = 0')
            ->where('(' . $database->quoteName('lastResetTime') . ' < ' . $database->quote($cutoffDate) .
                    ' OR ' . $database->quoteName('lastResetTime') . ' IS NULL' .
                    ' OR ' . $database->quoteName('lastResetTime') . ' = ' . $database->quote(
                        '0000-00-00 00:00:00',
                    ) . ')');

        $expiredCount = (int) $database->setQuery($query)
            ->loadResult();

        // Get total active users for percentage calculation
        $totalQuery = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__users'))
            ->where($database->quoteName('block') . ' = 0');

        $totalUsers = (int) $database->setQuery($totalQuery)
            ->loadResult();

        if ($expiredCount > 0 && $totalUsers > 0) {
            // Calculate percentage of users with expired passwords
            $percentage = round(($expiredCount / $totalUsers) * 100);

            // Critical threshold: >75% of users have expired passwords
            if ($percentage > 75) {
                return $this->warning(
                    sprintf(
                        '%d of %d active users (%d%%) have not changed their password in over %d days. Consider implementing a password policy.',
                        $expiredCount,
                        $totalUsers,
                        $percentage,
                        self::PASSWORD_EXPIRY_DAYS,
                    ),
                );
            }

            // Warning threshold: >25% of users have expired passwords
            if ($percentage > 25) {
                return $this->warning(
                    sprintf(
                        '%d of %d active users (%d%%) have not changed their password in over %d days. Consider reviewing password policies.',
                        $expiredCount,
                        $totalUsers,
                        $percentage,
                        self::PASSWORD_EXPIRY_DAYS,
                    ),
                );
            }

            // Some expired passwords but within acceptable threshold
            return $this->good(
                sprintf(
                    '%d of %d active users have not changed their password in over %d days (acceptable threshold).',
                    $expiredCount,
                    $totalUsers,
                    self::PASSWORD_EXPIRY_DAYS,
                ),
            );
        }

        return $this->good('All active users have recently updated passwords.');
    }
}

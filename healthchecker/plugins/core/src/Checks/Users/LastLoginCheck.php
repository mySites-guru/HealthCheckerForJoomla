<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Last Login (Never Logged In) Health Check
 *
 * This check identifies active (non-blocked) user accounts that have never logged
 * in to the site. It looks for users where lastvisitDate is NULL or set to the
 * zero date (0000-00-00 00:00:00).
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Users who have never logged in may indicate several issues: abandoned
 * registrations where users signed up but never activated or used their account,
 * spam bot registrations that were not blocked, accounts created by administrators
 * for users who never claimed them, or test accounts that were never cleaned up.
 * These accounts retain permissions and credentials but serve no active purpose,
 * representing unnecessary risk. They may also indicate issues with your
 * registration workflow (e.g., confirmation emails not being received).
 *
 * RESULT MEANINGS:
 *
 * GOOD: Fewer than 50 users have never logged in, or all active users have logged
 *       in at least once. A small number of never-logged-in accounts is normal
 *       (recent registrations, pending activations). The count is reported for
 *       awareness.
 *
 * WARNING: More than 50 active user accounts have never logged in. This high number
 *          suggests a need to review these accounts. Consider reaching out to users
 *          who registered but never logged in, or blocking/removing accounts that
 *          appear to be spam or abandoned.
 *
 * Note: This check does not produce CRITICAL results as never-logged-in accounts
 * are an organizational concern rather than an immediate security threat.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Users;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class LastLoginCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug for this check.
     *
     * @return string The check slug in format: users.last_login
     */
    public function getSlug(): string
    {
        return 'users.last_login';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug: users
     */
    public function getCategory(): string
    {
        return 'users';
    }

    /**
     * Perform the last login health check.
     *
     * Identifies active (non-blocked) user accounts that have never logged in by checking
     * for users where lastvisitDate is NULL or set to the zero date (0000-00-00 00:00:00).
     *
     * Returns WARNING if more than 50 users have never logged in, GOOD otherwise.
     * The total count of never-logged-in users and total active users is included
     * in all result messages for context.
     *
     * @return HealthCheckResult The result with status (GOOD/WARNING) and message
     */
    /**
     * Perform the Last Login health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Find users who have never logged in (lastvisitDate is NULL or zero date)
        // Only checking non-blocked users since blocked users are already inactive
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__users'))
            ->where($database->quoteName('block') . ' = 0')
            ->where('(' . $database->quoteName('lastvisitDate') . ' IS NULL' .
                    ' OR ' . $database->quoteName('lastvisitDate') . ' = ' . $database->quote(
                        '0000-00-00 00:00:00',
                    ) . ')');

        $neverLoggedInCount = (int) $database->setQuery($query)
            ->loadResult();

        // Get total active users for context in the result message
        $totalQuery = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__users'))
            ->where($database->quoteName('block') . ' = 0');

        $totalUsers = (int) $database->setQuery($totalQuery)
            ->loadResult();

        // Threshold of 50+ never-logged-in users indicates potential issues with
        // registration workflow, spam registrations, or abandoned accounts
        if ($neverLoggedInCount > 50) {
            return $this->warning(
                sprintf(
                    '%d of %d active users have never logged in. Consider reviewing these accounts.',
                    $neverLoggedInCount,
                    $totalUsers,
                ),
            );
        }

        // Small number of never-logged-in users is normal (recent registrations, pending activations)
        if ($neverLoggedInCount > 0) {
            return $this->good(
                sprintf('%d of %d active user(s) have never logged in.', $neverLoggedInCount, $totalUsers),
            );
        }

        return $this->good('All active users have logged in at least once.');
    }
}

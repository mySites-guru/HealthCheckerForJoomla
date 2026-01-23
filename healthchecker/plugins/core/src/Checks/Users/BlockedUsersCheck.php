<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Blocked Users Health Check
 *
 * This check counts the number of user accounts that have been blocked in your
 * Joomla installation. Blocked users cannot log in but their account records
 * remain in the database.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * While blocked accounts cannot access your site, a large accumulation of blocked
 * users can indicate various issues: spam registration attempts, brute force
 * attack remnants, or simply poor housekeeping. Over time, blocked accounts
 * clutter your user database, making user management more difficult and potentially
 * slowing down user-related queries. Regular cleanup of blocked accounts that
 * are no longer needed helps maintain database efficiency and cleaner user lists.
 *
 * RESULT MEANINGS:
 *
 * GOOD: 50 or fewer blocked accounts. This is a manageable number that does not
 *       indicate any immediate concerns. The count is reported for awareness.
 *
 * WARNING: More than 50 blocked user accounts exist. This accumulation suggests
 *          you should review and clean up old blocked accounts. Consider deleting
 *          accounts that were blocked due to spam registration or that are no
 *          longer relevant.
 *
 * Note: This check does not produce CRITICAL results as blocked accounts cannot
 * access your site. The concern is primarily about database hygiene and management.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Users;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class BlockedUsersCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique identifier for this check.
     *
     * @return string The check slug in the format 'users.blocked_users'
     */
    public function getSlug(): string
    {
        return 'users.blocked_users';
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
     * Performs the blocked users health check.
     *
     * Counts the number of blocked user accounts in the database. While blocked
     * accounts cannot access the site, excessive accumulation can indicate spam
     * registrations or poor housekeeping and may slow down user-related queries.
     *
     * @return HealthCheckResult WARNING if >50 blocked accounts, GOOD otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Count users where block field = 1 (blocked accounts)
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__users'))
            ->where($database->quoteName('block') . ' = 1');

        $blockedCount = (int) $database->setQuery($query)
            ->loadResult();

        // Threshold: More than 50 blocked accounts suggests cleanup needed
        if ($blockedCount > 50) {
            return $this->warning(
                sprintf('%d blocked user accounts. Consider cleaning up old blocked accounts.', $blockedCount),
            );
        }

        return $this->good(sprintf('%d blocked user account(s).', $blockedCount));
    }
}

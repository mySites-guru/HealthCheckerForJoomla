<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Super Admin Count Health Check
 *
 * This check monitors the number of active Super Administrator accounts in your
 * Joomla installation by counting users in group ID 8 (Super Users) who are not blocked.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Super Administrators have unrestricted access to every aspect of your Joomla site,
 * including the ability to install extensions, modify configurations, and access
 * sensitive data. Each additional super admin account increases the attack surface
 * for your site. If any super admin account is compromised, an attacker gains
 * complete control. Following the principle of least privilege, only a minimal
 * number of trusted individuals should have this level of access.
 *
 * RESULT MEANINGS:
 *
 * GOOD: 1-3 super admin accounts exist. This is an appropriate number for most
 *       sites, allowing for primary admin access with a backup account.
 *
 * WARNING: 4-5 super admin accounts exist. This is higher than recommended.
 *          Review whether all accounts truly need super admin privileges, and
 *          consider demoting some to Administrator or lower roles.
 *
 * CRITICAL: More than 5 super admin accounts exist, OR no super admins exist.
 *           Too many super admins is a significant security risk. Zero super
 *           admins may indicate a configuration issue or database corruption.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Users;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class SuperAdminCountCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique identifier for this check.
     *
     * @return string The check slug in the format 'users.super_admin_count'
     */
    public function getSlug(): string
    {
        return 'users.super_admin_count';
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
     * Performs the super administrator count health check.
     *
     * Counts active Super Administrator accounts (group ID 8) and evaluates whether
     * the number follows security best practices. Too many super admins increases
     * attack surface; zero super admins indicates a configuration problem.
     *
     * @return HealthCheckResult CRITICAL if >5 or 0 super admins, WARNING if >3, GOOD if 1-3
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Count active users in Super Users group (ID 8)
        // Join #__users with #__user_usergroup_map to find group membership
        // Only count non-blocked (active) users
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__users', 'u'))
            ->join(
                'INNER',
                $database->quoteName('#__user_usergroup_map', 'm'),
                $database->quoteName('u.id') . ' = ' . $database->quoteName('m.user_id'),
            )
            ->where($database->quoteName('m.group_id') . ' = 8') // Super Users group
            ->where($database->quoteName('u.block') . ' = 0');   // Only active users

        $database->setQuery($query);
        $count = (int) $database->loadResult();

        // Critical threshold: More than 5 super admins is excessive
        if ($count > 5) {
            return $this->critical(
                sprintf(
                    'There are %d Super Admin users. This is a significant security risk - consider reducing this number.',
                    $count,
                ),
            );
        }

        // Warning threshold: More than 3 super admins is higher than recommended
        if ($count > 3) {
            return $this->warning(
                sprintf('There are %d Super Admin users. Consider reducing this for better security.', $count),
            );
        }

        // Critical: No super admins indicates a serious configuration problem
        if ($count === 0) {
            return $this->critical('No active Super Admin users found. This may indicate a configuration issue.');
        }

        // Optimal range: 1-3 super admin accounts
        return $this->good(sprintf('%d Super Admin user(s) found.', $count));
    }
}

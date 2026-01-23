<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Admin Username Health Check
 *
 * This check verifies that Super Administrator accounts do not use common,
 * easily-guessed usernames like "admin", "administrator", "root", or "joomla".
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Brute force attacks typically try common usernames first. If an attacker knows
 * your admin username is "admin", they only need to guess the password. Using a
 * unique, non-obvious username adds an extra layer of security by making attackers
 * guess both the username and password.
 *
 * RESULT MEANINGS:
 *
 * GOOD: No Super Administrator accounts use common insecure usernames. Attackers
 *       cannot easily guess admin account usernames for brute force attacks.
 *
 * WARNING: Not applicable for this check.
 *
 * CRITICAL: One or more Super Administrator accounts have common usernames like
 *           "admin", "administrator", "root", "superuser", or "joomla". Change these
 *           usernames immediately to unique, non-guessable values.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class AdminUsernameCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug for this health check
     *
     * @return string The check slug in format 'security.admin_username'
     */
    public function getSlug(): string
    {
        return 'security.admin_username';
    }

    /**
     * Get the category this check belongs to
     *
     * @return string The category slug 'security'
     */
    public function getCategory(): string
    {
        return 'security';
    }

    /**
     * Perform the admin username security health check
     *
     * Verifies that Super Administrator accounts do not use common, predictable usernames
     * that are frequently targeted in brute force attacks. Checks against a list of
     * well-known insecure usernames.
     *
     * Security considerations:
     * - Brute force attacks typically try common usernames first (admin, administrator, root)
     * - Using unique usernames adds security through obscurity - attackers must guess both username AND password
     * - This is not a replacement for strong passwords and MFA, but provides an additional hurdle
     * - Only checks Super Admins (group_id = 8) as they have highest privilege
     * - Case-insensitive comparison to catch Admin, ADMIN, AdMiN, etc.
     *
     * @return HealthCheckResult Result indicating if insecure usernames are in use
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // List of common usernames targeted by attackers
        // These are frequently used in automated brute force attacks
        $insecureUsernames = ['admin', 'administrator', 'root', 'superuser', 'joomla'];

        $query = $database->getQuery(true)
            ->select($database->quoteName('u.username'))
            ->from($database->quoteName('#__users', 'u'))
            ->join(
                'INNER',
                $database->quoteName('#__user_usergroup_map', 'm'),
                $database->quoteName('u.id') . ' = ' . $database->quoteName('m.user_id'),
            )
            ->where($database->quoteName('m.group_id') . ' = 8') // Super Users group
            ->where($database->quoteName('u.block') . ' = 0'); // Only active accounts

        // Build case-insensitive IN clause for insecure usernames
        // Using LOWER() ensures we catch Admin, ADMIN, AdMiN, etc.
        $quotedUsernames = array_map(
            fn(string $username): string|array => $database->quote(strtolower((string) $username)),
            $insecureUsernames,
        );

        $query->where('LOWER(' . $database->quoteName('u.username') . ') IN (' . implode(',', $quotedUsernames) . ')');

        $foundUsernames = $database->setQuery($query)
            ->loadColumn();

        if ($foundUsernames !== []) {
            $count = count($foundUsernames);
            $usernames = implode(', ', $foundUsernames);

            return $this->critical(
                sprintf(
                    '%d Super Admin account(s) have insecure username(s): %s. Change these to unique, non-guessable usernames.',
                    $count,
                    $usernames,
                ),
            );
        }

        return $this->good('No Super Admin accounts have common insecure usernames.');
    }
}

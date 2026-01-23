<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Two-Factor Authentication (MFA) Health Check
 *
 * This check verifies that Multi-Factor Authentication plugins are enabled in Joomla.
 * MFA adds an additional layer of security beyond username and password.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Passwords can be compromised through phishing, data breaches, or brute force attacks.
 * MFA requires users to provide a second verification factor (such as a time-based code
 * from an authenticator app), making account compromise significantly more difficult
 * even if passwords are stolen.
 *
 * RESULT MEANINGS:
 *
 * GOOD: One or more Multi-Factor Authentication plugins are enabled. Administrators
 *       should configure MFA on their accounts for enhanced security.
 *
 * WARNING: No MFA plugins are enabled. Consider enabling MFA plugins (such as the
 *          built-in Authenticator plugin) and requiring administrators to set up
 *          two-factor authentication on their accounts.
 *
 * CRITICAL: Not applicable for this check.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class TwoFactorAuthCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug for this health check
     *
     * @return string The check slug in format 'security.two_factor_auth'
     */
    public function getSlug(): string
    {
        return 'security.two_factor_auth';
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
     * Perform the Multi-Factor Authentication health check
     *
     * Verifies that:
     * 1. At least one MFA plugin is enabled in the 'multifactorauth' folder
     * 2. Super Administrator accounts have MFA configured in the #__user_mfa table
     *
     * Security considerations:
     * - MFA provides defense against password compromise from phishing, breaches, or brute force
     * - Only checks Super Admins (group_id = 8) as they have highest privilege
     * - Presence in #__user_mfa table indicates at least one MFA method is configured
     * - Common MFA plugins: totp (Authenticator app), webauthn (hardware keys), yubikey
     *
     * @return HealthCheckResult Result indicating MFA plugin and usage status
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Count enabled MFA plugins (multifactorauth folder)
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('multifactorauth'))
            ->where($database->quoteName('enabled') . ' = 1');

        $enabled2faPlugins = (int) $database->setQuery($query)
            ->loadResult();

        if ($enabled2faPlugins === 0) {
            return $this->warning(
                'No Multi-Factor Authentication plugins are enabled. Consider enabling 2FA for better security.',
            );
        }

        // Count total active Super Admin accounts (group_id = 8, not blocked)
        $query = $database->getQuery(true)
            ->select('COUNT(DISTINCT ' . $database->quoteName('u.id') . ')')
            ->from($database->quoteName('#__users', 'u'))
            ->join('INNER', $database->quoteName('#__user_usergroup_map', 'm') . ' ON u.id = m.user_id')
            ->join('LEFT', $database->quoteName('#__user_mfa', 'mfa') . ' ON u.id = mfa.user_id')
            ->where($database->quoteName('m.group_id') . ' = 8') // Super Users group
            ->where($database->quoteName('u.block') . ' = 0');

        $totalSuperAdmins = (int) $database->setQuery($query)
            ->loadResult();

        // Count Super Admins with at least one MFA method configured
        // INNER join to #__user_mfa ensures they have MFA records
        $query = $database->getQuery(true)
            ->select('COUNT(DISTINCT ' . $database->quoteName('u.id') . ')')
            ->from($database->quoteName('#__users', 'u'))
            ->join('INNER', $database->quoteName('#__user_usergroup_map', 'm') . ' ON u.id = m.user_id')
            ->join('INNER', $database->quoteName('#__user_mfa', 'mfa') . ' ON u.id = mfa.user_id')
            ->where($database->quoteName('m.group_id') . ' = 8')
            ->where($database->quoteName('u.block') . ' = 0');

        $superAdminsWithMFA = (int) $database->setQuery($query)
            ->loadResult();

        // Plugins enabled but NO Super Admins have MFA configured
        if ($totalSuperAdmins > 0 && $superAdminsWithMFA === 0) {
            return $this->warning(
                sprintf(
                    '%d MFA plugin(s) enabled but no Super Admins have MFA configured. Configure MFA for administrator accounts.',
                    $enabled2faPlugins,
                ),
            );
        }

        // Some but not all Super Admins have MFA configured
        if ($superAdminsWithMFA < $totalSuperAdmins) {
            return $this->warning(
                sprintf(
                    '%d MFA plugin(s) enabled. %d of %d Super Admins have MFA configured. Configure MFA for all administrators.',
                    $enabled2faPlugins,
                    $superAdminsWithMFA,
                    $totalSuperAdmins,
                ),
            );
        }

        // All Super Admins have MFA configured
        return $this->good(
            sprintf(
                '%d MFA plugin(s) enabled and all %d Super Admin(s) have MFA configured.',
                $enabled2faPlugins,
                $totalSuperAdmins,
            ),
        );
    }
}

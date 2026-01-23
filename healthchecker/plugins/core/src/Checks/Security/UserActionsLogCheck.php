<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * User Actions Log Plugin Enabled Health Check
 *
 * This check verifies that the User Actions Log plugin is enabled to track
 * user authentication events and other important actions.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The User Actions Log plugin records important user activities including logins,
 * logouts, and other security-relevant events. Without this logging, you cannot
 * detect suspicious activity, investigate security incidents, or maintain an
 * audit trail of user actions on your site.
 *
 * RESULT MEANINGS:
 *
 * GOOD: User Actions Log and Action Log plugins are enabled. User activities are
 *       being recorded for security monitoring. Review Action Logs regularly for
 *       suspicious activity.
 *
 * WARNING: Either the User Actions Log plugin is not installed/enabled, or Action
 *          Log plugins are disabled. Enable both for comprehensive activity tracking.
 *
 * CRITICAL: Not applicable for this check.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class UserActionsLogCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug for this health check
     *
     * @return string The check slug in format 'security.user_actions_log'
     */
    public function getSlug(): string
    {
        return 'security.user_actions_log';
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
     * Perform the User Actions Log plugin enabled health check
     *
     * Verifies that user activity logging is enabled via:
     * 1. User - User Actions Log plugin (tracks user authentication events)
     * 2. Action Log plugins (store logs in #__action_logs table)
     *
     * Security considerations:
     * - This check verifies activity logging is enabled
     * - User logs allow administrators to track important user actions
     * - Without logging, security incidents cannot be investigated
     * - Logs are stored in #__action_logs and viewable in Users > User Actions Log menu
     *
     * @return HealthCheckResult Result indicating User Actions Log configuration status
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Check if User - User Actions Log plugin is enabled
        // This plugin captures user login/logout events
        $query = $database
            ->getQuery(true)
            ->select($database->quoteName('enabled'))
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('user'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('userlog'));

        $enabled = $database->setQuery($query)
            ->loadResult();

        if (! $enabled) {
            return $this->warning(
                'User - User Actions Log plugin is disabled. Enable it to track user activities and login attempts.',
            );
        }

        // Check if Action Log plugins are enabled (e.g., actionlog/joomla)
        // These plugins store the actual log entries in the database
        // Without these, the User Actions Log plugin has nowhere to send events
        $query = $database
            ->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('actionlog'))
            ->where($database->quoteName('enabled') . ' = 1');

        $actionlogCount = (int) $database->setQuery($query)
            ->loadResult();

        if ($actionlogCount === 0) {
            return $this->warning(
                'User Actions Log is enabled but Action Log plugins are disabled. Enable Action Log plugins for full activity tracking.',
            );
        }

        return $this->good(
            'User Actions Log plugin is enabled. User activities are being recorded for security monitoring.',
        );
    }
}

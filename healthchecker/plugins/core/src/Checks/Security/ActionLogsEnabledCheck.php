<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Action Logs Health Check
 *
 * This check verifies that Joomla's Action Logs plugins are enabled. Action logs
 * record administrative activities such as content changes, user management,
 * configuration updates, and login attempts.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Security auditing requires comprehensive logs of user activities. Action logs help
 * detect unauthorized changes, investigate security incidents, meet compliance
 * requirements, and provide an audit trail. Without logging, you cannot effectively
 * monitor what administrators are doing or detect suspicious behavior.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Action log plugins are enabled, recording administrative activities for
 *       security auditing. Review logs regularly in System > Action Logs.
 *
 * WARNING: Action log plugins are disabled. Enable them in Plugins to track user
 *          activity. This is essential for security monitoring and compliance.
 *
 * CRITICAL: Not applicable for this check.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ActionLogsEnabledCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'security.action_logs_enabled'
     */
    public function getSlug(): string
    {
        return 'security.action_logs_enabled';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'security'
     */
    public function getCategory(): string
    {
        return 'security';
    }

    /**
     * Perform the Action Logs Enabled health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Check if actionlog plugins are enabled
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('actionlog'))
            ->where($database->quoteName('enabled') . ' = 1');

        $enabledPlugins = (int) $database->setQuery($query)
            ->loadResult();

        if ($enabledPlugins === 0) {
            return $this->warning(
                'Action log plugins are disabled. Enable them to track user activity for security auditing.',
            );
        }

        return $this->good(sprintf('%d action log plugin(s) enabled for security auditing.', $enabledPlugins));
    }
}

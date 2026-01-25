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
 * This check verifies that Joomla's Action Logs system plugin is enabled. The system
 * plugin (plg_system_actionlogs) is the core logging engine that records administrative
 * activities such as content changes, user management, configuration updates, and
 * login attempts.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Security auditing requires comprehensive logs of user activities. Action logs help
 * detect unauthorized changes, investigate security incidents, meet compliance
 * requirements, and provide an audit trail. Without logging, you cannot effectively
 * monitor what administrators are doing or detect suspicious behavior.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The System - Action Logs plugin is enabled, recording administrative activities
 *       for security auditing. Review logs regularly in System > Action Logs.
 *
 * WARNING: The System - Action Logs plugin is disabled. Enable it in System > Plugins
 *          to track user activity. This is essential for security monitoring and compliance.
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
     * Checks if the core System - Action Logs plugin (plg_system_actionlogs) is enabled.
     * This is the main logging engine that must be active for action logging to work.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Check if the core System - Action Logs plugin is enabled
        // This plugin (folder: system, element: actionlogs) is the logging engine
        $query = $database->getQuery(true)
            ->select($database->quoteName('enabled'))
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('system'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('actionlogs'));

        $systemPluginEnabled = (int) $database->setQuery($query)
            ->loadResult();

        if ($systemPluginEnabled !== 1) {
            return $this->warning(
                'System - Action Logs plugin is disabled. Enable it to track user activity for security auditing.',
            );
        }

        return $this->good('Action Logs system plugin is enabled for security auditing.');
    }
}

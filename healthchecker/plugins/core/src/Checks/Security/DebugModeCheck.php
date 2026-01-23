<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Debug Mode Health Check
 *
 * This check verifies that Joomla's debug mode is disabled in production environments.
 * Debug mode controls the display of detailed error messages, query information,
 * and performance data directly in the browser.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Debug mode exposes sensitive information including database queries, file paths,
 * server configuration details, and stack traces. Attackers can use this information
 * to identify vulnerabilities and craft targeted attacks against your site.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Debug mode is disabled. No sensitive debugging information will be
 *       displayed to site visitors or potential attackers.
 *
 * WARNING: Debug mode is enabled. This exposes technical details that could help
 *          attackers compromise your site. Disable debug mode in Global Configuration
 *          unless actively troubleshooting issues.
 *
 * CRITICAL: Not applicable for this check.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class DebugModeCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'security.debug_mode'
     */
    public function getSlug(): string
    {
        return 'security.debug_mode';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category identifier 'security'
     */
    public function getCategory(): string
    {
        return 'security';
    }

    /**
     * Perform the debug mode security check.
     *
     * Verifies that Joomla's debug mode is disabled in production. Debug mode exposes
     * sensitive information including database queries, file paths, stack traces, and
     * server configuration details that attackers can use to identify vulnerabilities.
     *
     * @return HealthCheckResult WARNING if debug mode is enabled,
     *                          GOOD if debug mode is disabled
     */
    protected function performCheck(): HealthCheckResult
    {
        // Get the debug configuration setting from Global Configuration
        $config = Factory::getApplication()->get('debug', false);

        // Debug mode enabled - exposes sensitive technical information
        if ($config) {
            return $this->warning(
                'Debug mode is enabled. This should be disabled in production for security and performance.',
            );
        }

        // Debug mode disabled - no sensitive debugging information exposed
        return $this->good('Debug mode is disabled.');
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * API Authentication Health Check
 *
 * This check verifies that API authentication plugins are enabled for Joomla's
 * Web Services API. The API allows external applications to interact with your
 * Joomla site programmatically.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * If you use Joomla's Web Services API for headless CMS functionality, mobile apps,
 * or third-party integrations, you need authentication plugins enabled to secure
 * API access. Without authentication, the API endpoints will be inaccessible,
 * breaking any integrations that depend on them.
 *
 * RESULT MEANINGS:
 *
 * GOOD: One or more API authentication plugins are enabled (such as Token or Basic
 *       authentication). The API can be accessed by authorized applications.
 *
 * WARNING: No API authentication plugins are enabled. If you use or plan to use
 *          the Web Services API, enable the appropriate authentication plugins.
 *          If you don't use the API, this warning can be safely ignored.
 *
 * CRITICAL: Not applicable for this check.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ApiAuthCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug for this health check
     *
     * @return string The check slug in format 'security.api_auth'
     */
    public function getSlug(): string
    {
        return 'security.api_auth';
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
     * Perform the API authentication health check
     *
     * Verifies that at least one API authentication plugin (such as Token or Basic authentication)
     * is enabled. The Web Services API requires authentication plugins to secure access.
     *
     * Security considerations:
     * - Without authentication, API endpoints are completely inaccessible
     * - Common plugins: plg_api-authentication_token, plg_api-authentication_basic
     * - This check is primarily informational for sites using headless CMS features
     *
     * @return HealthCheckResult Result indicating if API authentication is configured
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Query all plugins in the 'api-authentication' folder to check their enabled status
        $query = $database->getQuery(true)
            ->select($database->quoteName(['element', 'enabled']))
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('api-authentication'));

        $plugins = $database->setQuery($query)
            ->loadObjectList();

        // Filter to only enabled plugins (enabled = 1)
        $enabledPlugins = array_filter($plugins, fn($p): bool => (int) $p->enabled === 1);

        if ($enabledPlugins === []) {
            return $this->warning(
                'No API authentication plugins are enabled. The Web Services API will be inaccessible.',
            );
        }

        // Extract plugin element names for reporting
        $pluginNames = array_map(fn($p) => $p->element, $enabledPlugins);

        return $this->good(sprintf('API authentication enabled: %s', implode(', ', $pluginNames)));
    }
}

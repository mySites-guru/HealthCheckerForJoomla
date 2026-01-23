<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Disabled Extensions Health Check
 *
 * This check counts the number of disabled components, modules, and plugins
 * in your Joomla installation to identify potential cleanup opportunities.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Disabled extensions remain installed on the server and can pose security risks
 * if they contain vulnerabilities, even when not active. They also add clutter
 * to the admin interface and may consume database space. Uninstalling unused
 * extensions keeps your site lean and reduces the attack surface.
 *
 * RESULT MEANINGS:
 *
 * GOOD: You have 20 or fewer disabled extensions. This is within acceptable limits.
 *
 * WARNING: You have more than 20 disabled extensions. Consider uninstalling
 * extensions you no longer need rather than just disabling them.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class DisabledExtensionsCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'extensions.disabled_extensions'
     */
    public function getSlug(): string
    {
        return 'extensions.disabled_extensions';
    }

    /**
     * Returns the category this check belongs to.
     *
     * @return string The category slug 'extensions'
     */
    public function getCategory(): string
    {
        return 'extensions';
    }

    /**
     * Performs the disabled extensions health check.
     *
     * This method queries the #__extensions table to count disabled extensions of types
     * that can be enabled/disabled: components, modules, and plugins. It excludes other
     * extension types like languages, templates, libraries, packages, and files which
     * don't have an enabled/disabled state in the same way.
     *
     * A threshold of 20 disabled extensions is used as a guideline. Having many disabled
     * extensions suggests cleanup is needed - they should be uninstalled rather than
     * left disabled, as disabled extensions still exist on the filesystem and in the
     * database, potentially posing security risks if they contain vulnerabilities.
     *
     * @return HealthCheckResult WARNING if more than 20 extensions are disabled, GOOD otherwise
     */
    /**
     * Perform the Disabled Extensions health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Count disabled extensions, but only for types that can be enabled/disabled
        // We exclude: language, template, library, package, file (these don't have enabled state)
        // We include: component, module, plugin (these can be enabled/disabled)
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('enabled') . ' = 0')
            ->where($database->quoteName('type') . ' IN (' . implode(',', [
                $database->quote('component'),  // Backend components (com_*)
                $database->quote('module'),     // Frontend/backend modules (mod_*)
                $database->quote('plugin'),     // System/content/other plugins (plg_*)
            ]) . ')');

        $disabledCount = (int) $database->setQuery($query)
            ->loadResult();

        // Threshold of 20+ disabled extensions suggests cleanup needed
        if ($disabledCount > 20) {
            return $this->warning(
                sprintf('%d extensions are disabled. Consider uninstalling unused extensions.', $disabledCount),
            );
        }

        return $this->good(sprintf('%d extension(s) disabled.', $disabledCount));
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Apache Modules Health Check
 *
 * This check verifies that required and recommended Apache modules are installed when
 * running on Apache web server. It specifically checks for mod_rewrite (required for
 * SEF URLs) and recommends mod_headers, mod_expires, and mod_deflate for performance.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Joomla's Search Engine Friendly (SEF) URLs require mod_rewrite to function. Without
 * it, the site can only use non-SEF URLs with query strings. The recommended modules
 * enable security headers, browser caching, and compression - all important for
 * performance and security best practices.
 *
 * RESULT MEANINGS:
 *
 * GOOD: When running on Apache with required modules present. If not all recommended
 * modules are detected, those are listed for informational purposes but the check
 * still passes. When not running on Apache (nginx, LiteSpeed, etc.), this check
 * gracefully skips as the modules are not applicable.
 *
 * WARNING: mod_rewrite is not detected. SEF URLs will not work correctly with Apache.
 * Either enable mod_rewrite or configure nginx/other server equivalently.
 *
 * CRITICAL: This check does not produce critical results.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ApacheModulesCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.apache_modules'
     */
    public function getSlug(): string
    {
        return 'system.apache_modules';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'system'
     */
    public function getCategory(): string
    {
        return 'system';
    }

    /**
     * Verify that required and recommended Apache modules are loaded.
     *
     * When running on Apache web server, this check verifies that mod_rewrite
     * is available (required for SEF URLs) and recommends additional modules
     * for optimal performance and security:
     * - mod_headers: Security headers and custom HTTP headers
     * - mod_expires: Browser caching control
     * - mod_deflate: Response compression
     *
     * If not running on Apache or the apache_get_modules() function is unavailable,
     * the check gracefully skips as these modules are Apache-specific.
     *
     * @return HealthCheckResult GOOD if not on Apache or modules not detectable,
     *                           WARNING if required modules (mod_rewrite) are missing,
     *                           GOOD with notes if only recommended modules are missing,
     *                           GOOD if all modules present
     */
    protected function performCheck(): HealthCheckResult
    {
        // Not running on Apache or function not available
        if (! \function_exists('apache_get_modules')) {
            return $this->good('Not running on Apache or module detection not available.');
        }

        $modules = apache_get_modules();
        $requiredModules = ['mod_rewrite'];
        $recommendedModules = ['mod_headers', 'mod_expires', 'mod_deflate'];

        // Check for required modules (mod_rewrite for SEF URLs)
        $missing = [];
        foreach ($requiredModules as $requiredModule) {
            if (! \in_array($requiredModule, $modules, true)) {
                $missing[] = $requiredModule;
            }
        }

        if ($missing !== []) {
            return $this->warning(sprintf('Required Apache modules may be missing: %s', implode(', ', $missing)));
        }

        // Check for recommended performance/security modules
        $missingRecommended = [];
        foreach ($recommendedModules as $recommendedModule) {
            if (! \in_array($recommendedModule, $modules, true)) {
                $missingRecommended[] = $recommendedModule;
            }
        }

        if ($missingRecommended !== []) {
            return $this->good(
                sprintf('Core modules OK. Optional modules not detected: %s', implode(', ', $missingRecommended)),
            );
        }

        return $this->good('All recommended Apache modules are installed.');
    }
}

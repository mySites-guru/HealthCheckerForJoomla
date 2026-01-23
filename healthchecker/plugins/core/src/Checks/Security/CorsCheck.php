<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * CORS (Cross-Origin Resource Sharing) Health Check
 *
 * This check verifies the CORS configuration. CORS controls which external domains
 * can make requests to your site's API and resources via JavaScript.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * CORS is a security feature that prevents malicious websites from making unauthorized
 * requests to your site on behalf of users. If CORS is enabled with a wildcard (*),
 * any website can make API requests, potentially accessing user data or performing
 * actions. CORS should either be disabled or restricted to specific trusted domains.
 *
 * RESULT MEANINGS:
 *
 * GOOD: CORS is either disabled (blocking all cross-origin requests by default) or
 *       is enabled but restricted to specific trusted domains.
 *
 * WARNING: CORS is enabled with a wildcard (*) origin, allowing any website to make
 *          cross-origin requests. If you use the API, restrict CORS to only the
 *          specific domains that need access.
 *
 * CRITICAL: Not applicable for this check.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class CorsCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'security.cors'
     */
    public function getSlug(): string
    {
        return 'security.cors';
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
     * Perform the CORS configuration security check.
     *
     * Verifies that CORS (Cross-Origin Resource Sharing) is either disabled or properly
     * restricted to specific domains. A wildcard CORS configuration allows any external
     * website to make API requests to your site, potentially accessing user data.
     *
     * @return HealthCheckResult WARNING if CORS is enabled with wildcard (*),
     *                          GOOD if disabled or restricted to specific domains
     */
    protected function performCheck(): HealthCheckResult
    {
        $cmsApplication = Factory::getApplication();
        $cors = $cmsApplication->get('cors', false);

        // Check if CORS is enabled (accepts boolean true, string '1', or integer 1)
        if (in_array($cors, [true, '1', 1], true)) {
            // CORS is enabled - check if it's wildcard or properly restricted
            $corsAllowOrigin = $cmsApplication->get('cors_allow_origin', '*');

            // Wildcard allows any domain to make cross-origin requests - security risk
            if ($corsAllowOrigin === '*') {
                return $this->warning(
                    'CORS is enabled with wildcard (*) origin. Consider restricting to specific trusted domains for better security.',
                );
            }

            // CORS is restricted to specific domains - good security
            return $this->good(sprintf('CORS is enabled and restricted to: %s', $corsAllowOrigin));
        }

        // CORS disabled - default browser same-origin policy applies
        return $this->good('CORS is disabled. Cross-origin requests are blocked by default.');
    }
}

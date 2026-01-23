<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * HTTPS Redirect Health Check
 *
 * This check verifies that HTTP traffic is properly redirected to HTTPS. It checks
 * both Joomla's Force SSL setting and .htaccess redirect rules.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Even if your site supports HTTPS, users may access it via HTTP from bookmarks,
 * links, or direct URL entry. Without a redirect, these connections remain unencrypted.
 * All HTTP traffic should be automatically redirected to HTTPS to ensure encryption
 * for all visitors.
 *
 * RESULT MEANINGS:
 *
 * GOOD: HTTPS redirect is properly configured either through Joomla's Force SSL
 *       setting (entire site) or via .htaccess redirect rules. All HTTP requests
 *       are redirected to secure HTTPS connections.
 *
 * WARNING: HTTPS is only enforced for administrator (Force SSL = 1), or the site
 *          uses HTTPS but no redirect is configured for HTTP visitors. Configure
 *          Force SSL for the entire site or add .htaccess redirect rules.
 *
 * CRITICAL: HTTPS redirect is not configured and the site is not using SSL. All
 *           traffic is unencrypted. Enable Force SSL in Global Configuration or
 *           configure .htaccess redirect immediately.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class HttpsRedirectCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'security.https_redirect'
     */
    public function getSlug(): string
    {
        return 'security.https_redirect';
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
     * Perform the HTTPS redirect configuration check.
     *
     * Verifies that HTTP traffic is properly redirected to HTTPS to ensure all connections
     * are encrypted. Checks both Joomla's Force SSL setting and .htaccess redirect rules.
     * Even if a site supports HTTPS, users may access it via HTTP from bookmarks or links,
     * leaving those connections unencrypted without a proper redirect.
     *
     * @return HealthCheckResult CRITICAL if no HTTPS redirect is configured and site is not using SSL,
     *                          WARNING if Force SSL is admin-only or HTTPS is used without redirect,
     *                          GOOD if HTTPS redirect is properly configured via Force SSL or .htaccess
     */
    protected function performCheck(): HealthCheckResult
    {
        // Get Force SSL setting: 0 = None, 1 = Administrator only, 2 = Entire site
        $forceSsl = (int) Factory::getApplication()->get('force_ssl', 0);
        // Check if current connection is using HTTPS
        $isHttps = Uri::getInstance()->isSsl();

        // Check .htaccess for HTTPS redirect rules
        $htaccessPath = JPATH_ROOT . '/.htaccess';
        $hasHtaccessRedirect = false;

        if (file_exists($htaccessPath)) {
            $htaccessContent = file_get_contents($htaccessPath);

            if ($htaccessContent !== false) {
                // Check for common HTTPS redirect patterns in .htaccess
                // These patterns indicate mod_rewrite rules that redirect HTTP to HTTPS
                $hasHtaccessRedirect = (
                    stripos($htaccessContent, 'RewriteCond %{HTTPS}') !== false ||
                    stripos($htaccessContent, 'RewriteRule ^(.*)$ https://') !== false ||
                    stripos($htaccessContent, 'https://%{HTTP_HOST}') !== false
                );
            }
        }

        // Force SSL enabled for entire site and currently using HTTPS - optimal
        if ($forceSsl === 2 && $isHttps) {
            return $this->good('HTTPS is enforced for the entire site via Joomla configuration.');
        }

        // Force SSL enabled but not currently on HTTPS - certificate issue
        if ($forceSsl === 2 && ! $isHttps) {
            return $this->warning(
                'Force SSL is enabled for entire site, but current connection is not HTTPS. Check SSL certificate configuration.',
            );
        }

        // .htaccess redirect configured and currently using HTTPS
        if ($hasHtaccessRedirect && $isHttps) {
            return $this->good('HTTPS redirect appears to be configured via .htaccess.');
        }

        // Force SSL only for administrator - frontend not protected
        if ($forceSsl === 1) {
            return $this->warning(
                'HTTPS is only enforced for administrator. Consider enabling Force SSL for the entire site (option 2).',
            );
        }

        // Not using HTTPS and no redirect configured - critical security issue
        if (! $isHttps && $forceSsl === 0 && ! $hasHtaccessRedirect) {
            return $this->critical(
                'HTTPS redirect is not configured. Enable Force SSL in Global Configuration or configure .htaccess redirect.',
            );
        }

        // Using HTTPS but no automatic redirect - users can still access via HTTP
        if ($isHttps && $forceSsl === 0 && ! $hasHtaccessRedirect) {
            return $this->warning(
                'Site is using HTTPS but no redirect is configured. Users accessing via HTTP will not be redirected.',
            );
        }

        // Fallback for any other configuration state
        return $this->good('HTTPS configuration appears correct.');
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Force SSL Health Check
 *
 * This check verifies that HTTPS is enforced for your Joomla site. The Force SSL
 * setting in Joomla can be configured for administrator only, entire site, or disabled.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * HTTPS encrypts all data transmitted between users and your site, protecting login
 * credentials, personal information, and session cookies from interception. Without
 * HTTPS, attackers on the same network can capture sensitive data. Search engines
 * also penalize non-HTTPS sites in rankings.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Force SSL is enabled for the entire site (option 2), ensuring all connections
 *       use secure HTTPS encryption.
 *
 * WARNING: Either Force SSL is set to administrator only (option 1), or the site
 *          is using HTTPS but Force SSL is disabled. Consider enabling Force SSL
 *          for the entire site to ensure all traffic is encrypted.
 *
 * CRITICAL: Force SSL is disabled and the site is not using HTTPS. Your site is
 *           transmitting data in plain text that can be intercepted. Enable SSL
 *           immediately to protect user data.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ForceSslCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'security.force_ssl'
     */
    public function getSlug(): string
    {
        return 'security.force_ssl';
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
     * Perform the Force SSL configuration check.
     *
     * Verifies that HTTPS is properly enforced for your Joomla site. Force SSL can be
     * configured to: 0 = disabled, 1 = administrator only, or 2 = entire site. HTTPS
     * encrypts all data transmission, protecting credentials, personal information, and
     * session cookies from interception.
     *
     * @return HealthCheckResult CRITICAL if Force SSL is disabled and site is not using HTTPS,
     *                          WARNING if Force SSL is disabled with HTTPS or only enabled for admin,
     *                          GOOD if Force SSL is enabled for the entire site
     */
    protected function performCheck(): HealthCheckResult
    {
        // Get Force SSL setting: -1 = Not set, 0 = None, 1 = Administrator only, 2 = Entire site
        // Note: Joomla 5 default is -1 (not set) - not stored in DB if using default
        $forceSsl = (int) Factory::getApplication()->get('force_ssl', -1);
        // Check if current connection is using HTTPS
        $isHttps = Uri::getInstance()->isSsl();

        // No SSL enforcement and not using HTTPS - critical security issue
        if (($forceSsl === -1 || $forceSsl === 0) && ! $isHttps) {
            return $this->critical('Force SSL is disabled and site is not using HTTPS. Enable SSL for security.');
        }

        // Using HTTPS but not enforcing it - users could still access via HTTP
        if (($forceSsl === -1 || $forceSsl === 0) && $isHttps) {
            return $this->warning(
                'Site is using HTTPS but Force SSL is disabled. Enable Force SSL to ensure all connections use HTTPS.',
            );
        }

        // Only enforcing SSL for administrator - frontend remains unprotected
        if ($forceSsl === 1) {
            return $this->warning(
                'Force SSL is set to Administrator only. Consider enabling for entire site (option 2).',
            );
        }

        // SSL enforced for entire site - optimal security
        if ($forceSsl === 2) {
            return $this->good('Force SSL is enabled for the entire site.');
        }

        // Fallback for any unexpected configuration
        return $this->good('SSL configuration appears correct.');
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * X-Frame-Options Health Check
 *
 * This check verifies that the X-Frame-Options header is enabled via Joomla's
 * HTTP Headers plugin. This header prevents your site from being embedded in
 * iframes on other websites.
 *
 * DEPRECATION NOTE:
 * X-Frame-Options is considered deprecated in favor of the Content-Security-Policy
 * "frame-ancestors" directive, which provides more flexibility (e.g., allowing
 * specific domains). However, X-Frame-Options is still recommended for backwards
 * compatibility with older browsers that don't support CSP frame-ancestors.
 * For best protection, use both headers together.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Clickjacking attacks embed your site in a transparent iframe on a malicious page.
 * Users think they are clicking on the attacker's page but are actually clicking
 * buttons on your site, potentially performing unintended actions like changing
 * settings or making purchases. X-Frame-Options prevents this attack.
 *
 * RESULT MEANINGS:
 *
 * GOOD: X-Frame-Options header is enabled via the HTTP Headers plugin. Your site
 *       cannot be embedded in iframes on other domains, preventing clickjacking.
 *
 * WARNING: Either the HTTP Headers plugin is missing, disabled, or not configured.
 *          Install/enable the plugin to configure X-Frame-Options.
 *
 * CRITICAL: X-Frame-Options is explicitly disabled in the HTTP Headers plugin. Your
 *           site is vulnerable to clickjacking attacks. Enable this header immediately.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class XFrameOptionsCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug for this health check
     *
     * @return string The check slug in format 'security.x_frame_options'
     */
    public function getSlug(): string
    {
        return 'security.x_frame_options';
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
     * Perform the X-Frame-Options header health check
     *
     * Verifies that the X-Frame-Options security header is enabled via Joomla's
     * System - HTTP Headers plugin. This header prevents clickjacking attacks by
     * controlling whether the site can be embedded in iframes.
     *
     * Security considerations:
     * - Clickjacking embeds your site in a transparent iframe to trick users into clicking
     * - X-Frame-Options: SAMEORIGIN allows only same-domain embedding
     * - X-Frame-Options: DENY blocks all iframe embedding
     * - Critical if disabled, as it represents intentional removal of default protection
     *
     * @return HealthCheckResult Result indicating X-Frame-Options configuration status
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Query the System - HTTP Headers plugin for enabled status and configuration
        $query = $database->getQuery(true)
            ->select([$database->quoteName('enabled'), $database->quoteName('params')])
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('folder') . ' = ' . $database->quote('system'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('httpheaders'));

        $result = $database->setQuery($query)
            ->loadObject();

        if ($result === null) {
            return $this->warning(
                'HTTP Headers plugin not found. Install and enable it to configure X-Frame-Options header.',
            );
        }

        if ((int) $result->enabled === 0) {
            return $this->warning(
                'HTTP Headers plugin is disabled. Enable it to configure X-Frame-Options for clickjacking protection.',
            );
        }

        // Decode plugin parameters (JSON stored in database)
        $params = json_decode((string) $result->params, true);

        if (! is_array($params) || $params === []) {
            return $this->warning(
                'HTTP Headers plugin is enabled but not configured. Configure X-Frame-Options to prevent clickjacking.',
            );
        }

        // Check if xframeoptions is enabled (default is 1/enabled)
        // 0 = explicitly disabled, 1 = enabled
        $xFrameOptions = $params['xframeoptions'] ?? 1;

        if ((int) $xFrameOptions === 0) {
            return $this->critical(
                'X-Frame-Options is disabled. Your site is vulnerable to clickjacking attacks. Enable this header.',
            );
        }

        return $this->good('X-Frame-Options header is enabled via HTTP Headers plugin.');
    }
}

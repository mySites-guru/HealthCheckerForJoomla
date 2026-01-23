<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Content Security Policy (CSP) Health Check
 *
 * This check verifies that Content Security Policy headers are configured via
 * Joomla's HTTP Headers plugin. CSP is a powerful defense against XSS attacks.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Cross-Site Scripting (XSS) attacks inject malicious scripts into web pages. CSP
 * headers tell browsers which sources of scripts, styles, images, and other resources
 * are allowed. Even if an attacker manages to inject malicious code, CSP can prevent
 * it from executing, providing defense-in-depth against XSS vulnerabilities.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The HTTP Headers plugin is enabled with Content Security Policy configured.
 *       Browsers will enforce restrictions on script and resource loading.
 *
 * WARNING: Either the HTTP Headers plugin is missing, disabled, not configured, or
 *          CSP is not enabled within the plugin. Install/enable the plugin and
 *          configure Content Security Policy for XSS protection.
 *
 * CRITICAL: Not applicable for this check.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ContentSecurityPolicyCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug for this health check
     *
     * @return string The check slug in format 'security.content_security_policy'
     */
    public function getSlug(): string
    {
        return 'security.content_security_policy';
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
     * Perform the Content Security Policy health check
     *
     * Verifies that CSP headers are enabled via Joomla's System - HTTP Headers plugin.
     * CSP provides defense-in-depth against Cross-Site Scripting (XSS) attacks by
     * instructing browsers which sources of scripts, styles, and other resources are allowed.
     *
     * Security considerations:
     * - CSP is a defense-in-depth measure - not a replacement for input validation
     * - Even if XSS vulnerabilities exist, CSP can prevent malicious script execution
     * - CSP directives control script-src, style-src, img-src, connect-src, etc.
     * - Misconfigured CSP can break site functionality, so this is WARNING not CRITICAL
     *
     * @return HealthCheckResult Result indicating CSP configuration status
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
                'HTTP Headers plugin not found. Install and enable it to configure Content Security Policy headers.',
            );
        }

        if ((int) $result->enabled === 0) {
            return $this->warning(
                'HTTP Headers plugin is disabled. Enable it to configure Content Security Policy and other security headers.',
            );
        }

        // Decode plugin parameters (JSON stored in database)
        $params = json_decode((string) $result->params, true);

        if (! is_array($params) || $params === []) {
            return $this->warning(
                'HTTP Headers plugin is enabled but not configured. Configure Content Security Policy for XSS protection.',
            );
        }

        // Check if CSP is enabled in plugin configuration
        // Default is 0 (disabled) as CSP requires careful configuration per site
        $cspEnabled = $params['contentsecuritypolicy'] ?? 0;

        if ((int) $cspEnabled === 0) {
            return $this->warning(
                'Content Security Policy is not enabled. Enable CSP in the HTTP Headers plugin to protect against XSS attacks.',
            );
        }

        return $this->good('Content Security Policy is enabled via HTTP Headers plugin.');
    }
}

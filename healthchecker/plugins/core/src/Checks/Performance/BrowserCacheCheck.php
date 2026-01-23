<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Browser Cache Health Check
 *
 * This check examines the .htaccess file for browser caching directives that
 * instruct browsers to cache static assets like images, CSS, and JavaScript.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Browser caching allows static assets to be stored locally in the visitor's
 * browser. On subsequent visits, these assets load from cache instead of being
 * re-downloaded, dramatically improving page load times for returning visitors
 * and reducing server bandwidth usage.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Browser caching rules are configured in .htaccess using Expires headers
 * and/or Cache-Control headers. Static assets will be cached by browsers.
 *
 * WARNING: No browser caching rules detected in .htaccess, or the .htaccess
 * file is missing or empty. Consider adding ExpiresByType or Cache-Control
 * rules for static asset types (images, CSS, JavaScript, fonts).
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class BrowserCacheCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'performance.browser_cache'
     */
    public function getSlug(): string
    {
        return 'performance.browser_cache';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category identifier 'performance'
     */
    public function getCategory(): string
    {
        return 'performance';
    }

    /**
     * Perform the browser cache health check.
     *
     * This method examines the .htaccess file for browser caching directives that
     * instruct browsers to cache static assets like images, CSS, and JavaScript files.
     * Browser caching dramatically improves page load times for returning visitors by
     * storing assets locally instead of re-downloading them on each visit.
     *
     * The check looks for these Apache directives:
     * - ExpiresByType or ExpiresDefault (mod_expires)
     * - Cache-Control headers
     * - mod_expires module references
     *
     * Returns:
     * - GOOD: Browser caching rules found using Expires and/or Cache-Control headers
     * - WARNING: No caching rules found, or mod_expires referenced without rules
     *
     * @return HealthCheckResult The result indicating browser caching configuration status
     */
    /**
     * Perform the Browser Cache health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $htaccessPath = JPATH_ROOT . '/.htaccess';

        // .htaccess file must exist to configure browser caching
        if (! file_exists($htaccessPath)) {
            return $this->warning('.htaccess file not found. Browser caching configuration cannot be verified.');
        }

        $htaccessContent = file_get_contents($htaccessPath);

        // Ensure .htaccess has content to analyze
        if (in_array($htaccessContent, ['', '0', false], true)) {
            return $this->warning('.htaccess file is empty. No browser caching rules configured.');
        }

        // Check for common browser caching directives (case-insensitive)
        $hasExpires = stripos($htaccessContent, 'ExpiresByType') !== false
            || stripos($htaccessContent, 'ExpiresDefault') !== false;
        $hasCacheControl = stripos($htaccessContent, 'Cache-Control') !== false;
        $hasModExpires = stripos($htaccessContent, 'mod_expires') !== false;

        // Active caching directives found - build descriptive message
        if ($hasExpires || $hasCacheControl) {
            $methods = [];

            if ($hasExpires) {
                $methods[] = 'Expires headers';
            }

            if ($hasCacheControl) {
                $methods[] = 'Cache-Control headers';
            }

            return $this->good(sprintf('Browser caching is configured using %s.', implode(' and ', $methods)));
        }

        // mod_expires module referenced but no actual caching rules
        if ($hasModExpires) {
            return $this->warning(
                'mod_expires module reference found but no ExpiresByType rules detected. Consider adding browser caching rules.',
            );
        }

        // No browser caching configuration found
        return $this->warning(
            'No browser caching rules detected in .htaccess. Consider adding ExpiresByType or Cache-Control headers for static assets.',
        );
    }
}

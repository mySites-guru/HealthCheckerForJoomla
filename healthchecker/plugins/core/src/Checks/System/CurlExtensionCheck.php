<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * cURL Extension Health Check
 *
 * This check verifies that the PHP cURL extension is loaded for HTTP requests.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The cURL extension enables Joomla to make outbound HTTP/HTTPS requests:
 * - Checking for Joomla and extension updates
 * - Fetching remote content and feeds
 * - OAuth and social login integrations
 * - Payment gateway communications
 * - API integrations with external services
 * - reCAPTCHA and other security service verification
 * While PHP's stream wrappers can handle some HTTP requests, cURL provides
 * better performance, security options, and compatibility.
 *
 * RESULT MEANINGS:
 *
 * GOOD: cURL extension is loaded with the reported libcurl version.
 *       All remote HTTP operations will work optimally.
 *
 * WARNING: cURL extension is not available. Joomla may fall back to stream
 *          wrappers for HTTP requests, but update checking, some integrations,
 *          and performance may be affected. Consider enabling cURL for better
 *          compatibility and security.
 *
 * Note: This check does not produce CRITICAL results as Joomla has fallback
 *       mechanisms, though functionality may be limited without cURL.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class CurlExtensionCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.curl_extension'
     */
    public function getSlug(): string
    {
        return 'system.curl_extension';
    }

    /**
     * Returns the category this check belongs to.
     *
     * @return string The category identifier 'system'
     */
    public function getCategory(): string
    {
        return 'system';
    }

    /**
     * Performs the cURL extension availability check.
     *
     * Verifies that the PHP cURL extension is loaded and attempts to retrieve
     * version information. Returns a warning if the extension is not available,
     * as Joomla can fall back to stream wrappers but with reduced functionality.
     *
     * @return HealthCheckResult Good status if cURL is loaded (with version if available),
     *                            Warning status if cURL extension is not loaded
     */
    protected function performCheck(): HealthCheckResult
    {
        // Check if cURL extension is available
        if (! extension_loaded('curl')) {
            return $this->warning(
                'cURL extension is not loaded. Update checks and some remote connections may not work.',
            );
        }

        // Retrieve cURL version information
        $version = curl_version();

        // Return basic success message if version info is unavailable
        if (! is_array($version) || ! isset($version['version'])) {
            return $this->good('cURL extension is loaded.');
        }

        // Return success with libcurl version number
        return $this->good(sprintf('cURL extension is loaded (libcurl %s).', $version['version']));
    }
}

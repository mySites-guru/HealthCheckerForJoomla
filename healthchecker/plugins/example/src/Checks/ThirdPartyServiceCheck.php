<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Third-Party Service Connectivity Health Check (Example)
 *
 * EXAMPLE TEMPLATE FOR THIRD-PARTY DEVELOPERS
 *
 * This example check demonstrates how to create a health check that:
 * - Uses a custom category registered by this plugin ('thirdparty')
 * - Doesn't require database access (pure logic/HTTP check)
 * - Checks external service connectivity using cURL
 * - Returns multiple status types based on performance and availability
 *
 * DEVELOPER NOTES:
 * - This is a reference implementation for third-party plugin developers
 * - Copy this pattern when creating checks that call external APIs/services
 * - Not all checks need database access - this demonstrates a standalone check
 * - Always handle missing dependencies gracefully (e.g., cURL not installed)
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Many Joomla sites depend on external services for updates, extensions,
 * and integrations. If your server cannot reach these services, critical
 * functionality may fail silently. This check verifies that outbound HTTP
 * connections work properly and respond in a timely manner.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The external service (Joomla API) is reachable and responding
 *       within an acceptable time frame (under 3 seconds).
 *
 * WARNING: The service is reachable but responding slowly (over 3 seconds).
 *          This may indicate network issues, server overload, or firewall
 *          configuration problems that could affect update checks.
 *
 * CRITICAL: The service cannot be reached at all. Check your server's
 *           internet connectivity, firewall rules, and whether outbound
 *           HTTP requests are allowed by your hosting provider.
 *
 * @subpackage  HealthChecker.Example
 * @since       1.0.0
 */

namespace MySitesGuru\HealthChecker\Plugin\Example\Checks;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined("_JEXEC") || die();

/**
 * Example health check demonstrating external service monitoring.
 *
 * This check shows how to:
 * - Use custom categories (defined in your plugin)
 * - Check external HTTP services
 * - Handle missing PHP extensions gracefully
 * - Return different statuses based on performance metrics
 *
 * @since  1.0.0
 */
final class ThirdPartyServiceCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique identifier for this health check.
     *
     * Format: {provider_slug}.{check_name}
     * - Must be lowercase
     * - Use underscores for spaces
     * - Must be unique across all plugins
     *
     * @return string The check slug in format 'provider.check_name'
     *
     * @since  1.0.0
     */
    public function getSlug(): string
    {
        return "example.thirdparty_service";
    }

    /**
     * Returns the category this check belongs to.
     *
     * This check uses a CUSTOM category ('thirdparty') that is registered
     * by the example plugin in its onCollectCategories() event handler.
     *
     * DEVELOPER NOTES:
     * - Custom categories must be registered before checks are collected
     * - See ExamplePlugin::onCollectCategories() for registration example
     * - Custom categories appear in the UI alongside core categories
     *
     * @return string The category slug 'thirdparty'
     *
     * @since  1.0.0
     */
    public function getCategory(): string
    {
        // Using a custom category registered by this plugin
        return "thirdparty";
    }

    /**
     * Returns the provider slug that owns this check.
     *
     * @return string The provider slug 'example'
     *
     * @since  1.0.0
     */
    public function getProvider(): string
    {
        return "example";
    }

    /**
     * Performs the actual health check logic.
     *
     * This check demonstrates:
     * - Testing external HTTP service availability
     * - Measuring response time for performance warnings
     * - Handling missing PHP extensions (cURL)
     * - Using multiple return status types
     *
     * PERFORMANCE NOTES:
     * - This check makes a real HTTP request (can be slow)
     * - Uses HEAD request to minimize data transfer
     * - Has timeout protection (10 seconds max)
     * - Consider caching results if this is too slow
     *
     * @return HealthCheckResult Result object with status and description
     *
     * @since  1.0.0
     */
    protected function performCheck(): HealthCheckResult
    {
        // PATTERN: In a real plugin, replace with your own service URL
        // This example uses Joomla's public API as a test endpoint
        $serviceUrl = "https://api.joomla.org/";

        // PATTERN: Extract complex checks to helper methods
        $isReachable = $this->checkServiceReachability($serviceUrl);

        $disableNote =
            ' To hide this, disable the "Health Checker - Example Provider" plugin in Extensions → Plugins.';

        // PATTERN: Use CRITICAL for complete failures
        if ($isReachable === false) {
            return $this->critical(
                "[EXAMPLE CHECK] Cannot reach Joomla API service. Check your internet connection or firewall settings." .
                    $disableNote,
            );
        }

        // PATTERN: Use WARNING for degraded performance
        if ($isReachable === "slow") {
            return $this->warning(
                "[EXAMPLE CHECK] Joomla API service is reachable but responding slowly." .
                    $disableNote,
            );
        }

        // PATTERN: GOOD status confirms everything is working
        return $this->good(
            "[EXAMPLE CHECK] Joomla API service is reachable and responding normally." .
                $disableNote,
        );
    }

    /**
     * Checks if a service URL is reachable via HTTP.
     *
     * This method demonstrates:
     * - Graceful handling of missing PHP extensions
     * - Measuring HTTP response times
     * - Using cURL for external service checks
     * - Returning multiple status types (true/false/string)
     *
     * DEVELOPER NOTES:
     * - Uses HEAD request to avoid downloading content
     * - Has both connection timeout (5s) and total timeout (10s)
     * - Follows redirects automatically
     * - Verifies SSL certificates for security
     * - Returns 'slow' for responses over 3 seconds
     *
     * @param   string  $url  The URL to check (must be absolute HTTP/HTTPS URL)
     *
     * @return bool|string true if reachable and fast,
     *                     'slow' if reachable but slow (>3 seconds),
     *                     false if unreachable
     *
     * @since  1.0.0
     */
    private function checkServiceReachability(string $url): bool|string
    {
        // PATTERN: Always check for required PHP extensions
        if (!\function_exists("curl_init")) {
            // Can't check without cURL, assume it's fine rather than failing
            // This prevents false negatives on servers without cURL
            return true;
        }

        // PATTERN: Initialize cURL with proper options
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, // Return response as string
            CURLOPT_NOBODY => true, // HEAD request (no body)
            CURLOPT_TIMEOUT => 10, // Total timeout (10 seconds)
            CURLOPT_CONNECTTIMEOUT => 5, // Connection timeout (5 seconds)
            CURLOPT_FOLLOWLOCATION => true, // Follow redirects
            CURLOPT_SSL_VERIFYPEER => true, // Verify SSL certificates
        ]);

        // PATTERN: Measure execution time for performance checks
        $startTime = microtime(true);
        curl_exec($ch);
        $duration = microtime(true) - $startTime;

        // PATTERN: Get response metadata
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        // PATTERN: Check for connection errors or invalid responses
        if ($error || $httpCode === 0) {
            return false;
        }

        // PATTERN: Warn about slow responses (may indicate issues)
        if ($duration > 3.0) {
            return "slow";
        }

        return true;
    }
}

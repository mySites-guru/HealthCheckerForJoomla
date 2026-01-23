<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Redirects Health Check
 *
 * This check examines Joomla's redirect configuration for redirect chains
 * (where one redirect leads to another) and redirect loops (where a URL
 * redirects to itself).
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Redirect chains slow down page loads because the browser must make multiple
 * requests before reaching the final destination. Redirect loops cause infinite
 * redirect errors, making pages inaccessible. Both issues negatively impact
 * SEO and user experience.
 *
 * RESULT MEANINGS:
 *
 * GOOD: No redirect chains or loops detected. Active redirects point directly
 * to their final destinations.
 *
 * WARNING: Redirect chains detected where one redirect destination is also
 * a redirect source. Simplify these by updating the first redirect to point
 * directly to the final destination.
 *
 * CRITICAL: Redirect loops detected where a URL redirects to itself or creates
 * a circular redirect path. This will cause infinite redirect errors and must
 * be fixed immediately.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class RedirectsCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'performance.redirects'
     */
    public function getSlug(): string
    {
        return 'performance.redirects';
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
     * Perform the redirects health check.
     *
     * This method examines Joomla's redirect configuration to identify performance
     * and functionality issues with redirects:
     *
     * - Redirect chains: Where one redirect destination is also a redirect source,
     *   requiring multiple HTTP requests to reach the final destination
     * - Redirect loops: Where a URL redirects to itself, causing infinite redirect errors
     *
     * The check performs these steps:
     * 1. Verifies com_redirect component is enabled
     * 2. Checks if redirect_links table exists
     * 3. Detects redirect chains using self-join query
     * 4. Identifies redirect loops where source equals destination
     *
     * Returns:
     * - GOOD: No redirect chains or loops detected
     * - WARNING: Redirect chains found that slow down page loads
     * - CRITICAL: Redirect loops found that cause infinite redirect errors
     *
     * @return HealthCheckResult The result indicating redirect configuration health
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Check if com_redirect component is installed and enabled
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('com_redirect'))
            ->where($database->quoteName('enabled') . ' = 1');

        $isEnabled = (int) $database->setQuery($query)
            ->loadResult() > 0;

        if (! $isEnabled) {
            return $this->good('Redirect component is not enabled.');
        }

        // Verify the redirect_links table exists in the database
        $tables = $database->getTableList();
        $prefix = $database->getPrefix();
        $tableName = $prefix . 'redirect_links';

        if (! in_array($tableName, $tables, true)) {
            return $this->good('Redirect links table not found.');
        }

        // Detect redirect chains using self-join: where r1.new_url = r2.old_url
        // This indicates r1 redirects to a URL that is itself a redirect
        $query = $database->getQuery(true)
            ->select($database->quoteName('r1.old_url'))
            ->from($database->quoteName('#__redirect_links', 'r1'))
            ->join(
                'INNER',
                $database->quoteName('#__redirect_links', 'r2') . ' ON ' .
                $database->quoteName('r1.new_url') . ' = ' . $database->quoteName('r2.old_url'),
            )
            ->where($database->quoteName('r1.published') . ' = 1')
            ->where($database->quoteName('r2.published') . ' = 1');

        $chains = $database->setQuery($query)
            ->loadColumn();
        $chainCount = count($chains);

        // Detect redirect loops: where old_url equals new_url (infinite redirects)
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__redirect_links'))
            ->where($database->quoteName('old_url') . ' = ' . $database->quoteName('new_url'))
            ->where($database->quoteName('published') . ' = 1');

        $loopCount = (int) $database->setQuery($query)
            ->loadResult();

        // Redirect loops are critical - they cause infinite redirect errors
        if ($loopCount > 0) {
            return $this->critical(
                sprintf(
                    'Found %d redirect loop(s) where source equals destination. This will cause infinite redirects.',
                    $loopCount,
                ),
            );
        }

        // Redirect chains are a warning - they slow down page loads
        if ($chainCount > 0) {
            return $this->warning(
                sprintf('Found %d redirect chain(s). Redirect chains can slow down page loads.', $chainCount),
            );
        }

        // Count total active redirects for informational purposes
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__redirect_links'))
            ->where($database->quoteName('published') . ' = 1');

        $totalRedirects = (int) $database->setQuery($query)
            ->loadResult();

        return $this->good(
            sprintf('No redirect chains or loops detected. %d active redirect(s) configured.', $totalRedirects),
        );
    }
}

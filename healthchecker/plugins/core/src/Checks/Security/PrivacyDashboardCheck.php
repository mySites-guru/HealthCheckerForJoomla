<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Privacy Dashboard Health Check
 *
 * This check verifies that Joomla's Privacy component is enabled and checks for
 * pending privacy requests. The Privacy component helps with GDPR compliance by
 * managing user data export and deletion requests.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * GDPR and similar privacy regulations require websites to respond to user requests
 * for data access and deletion within specific timeframes. The Privacy component
 * provides the tools to manage these requests. Pending requests that go unaddressed
 * may result in regulatory non-compliance and potential fines.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The Privacy component is enabled and there are no pending privacy requests.
 *       Your site is ready to handle user data requests for GDPR compliance.
 *
 * WARNING: Either the Privacy component is disabled, or there are pending privacy
 *          requests awaiting action. Enable the component and/or process outstanding
 *          requests in Users > Privacy > Requests.
 *
 * CRITICAL: Not applicable for this check.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class PrivacyDashboardCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'security.privacy_dashboard';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug
     */
    public function getCategory(): string
    {
        return 'security';
    }

    /**
     * Perform the privacy component and requests check.
     *
     * Verifies GDPR compliance readiness by checking:
     * 1. Whether the Privacy component (com_privacy) is enabled
     * 2. The number of pending privacy requests awaiting processing
     *
     * The Privacy component is essential for GDPR compliance as it manages
     * user data access and deletion requests. Pending requests must be
     * processed within regulatory timeframes to avoid non-compliance penalties.
     *
     * Database queries:
     * - Checks #__extensions table for enabled com_privacy component
     * - Counts pending requests (status = 0) in #__privacy_requests table
     *
     * @return HealthCheckResult Result indicating privacy compliance status:
     *                           - WARNING: Privacy component disabled OR pending requests exist
     *                           - GOOD: Component enabled with no pending requests
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Ensure database connection is available
        // Check if privacy component is installed and enabled
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('com_privacy'))
            ->where($database->quoteName('enabled') . ' = 1');

        $isEnabled = (int) $database->setQuery($query)
            ->loadResult() > 0;

        // Component must be enabled for GDPR compliance features
        if (! $isEnabled) {
            return $this->warning('Privacy component is disabled. Enable it for GDPR compliance features.');
        }

        // Check for pending privacy requests (status 0 = pending)
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__privacy_requests'))
            ->where($database->quoteName('status') . ' = 0');

        $pendingRequests = (int) $database->setQuery($query)
            ->loadResult();

        // Pending requests require timely action to maintain compliance
        if ($pendingRequests > 0) {
            return $this->warning(sprintf('%d pending privacy request(s) require attention.', $pendingRequests));
        }

        // Component enabled and no pending requests - ready for GDPR compliance
        return $this->good('Privacy component is enabled with no pending requests.');
    }
}

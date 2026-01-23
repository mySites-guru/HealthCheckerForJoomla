<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Error Reporting Health Check
 *
 * This check verifies that PHP error reporting is configured appropriately for
 * production environments. Error reporting controls what types of PHP errors,
 * warnings, and notices are displayed or logged.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Detailed error messages can reveal sensitive information about your server
 * configuration, file paths, database structure, and code logic. Attackers use
 * this information to identify vulnerabilities and exploit them.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Error reporting is set to "none" or "simple", which hides detailed
 *       error information from site visitors while still logging errors for
 *       administrator review.
 *
 * WARNING: Error reporting is set to "maximum" or "development". This displays
 *          detailed PHP errors, warnings, and notices that could expose sensitive
 *          information. Change to "none" or "simple" for production sites.
 *
 * CRITICAL: Not applicable for this check.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ErrorReportingCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'security.error_reporting'
     */
    public function getSlug(): string
    {
        return 'security.error_reporting';
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
     * Perform the error reporting security check.
     *
     * Verifies that PHP error reporting is configured appropriately for production.
     * Detailed error messages can reveal sensitive information about server configuration,
     * file paths, database structure, and code logic that attackers can exploit.
     *
     * @return HealthCheckResult WARNING if error reporting is set to maximum/development,
     *                          GOOD if set to none/simple or other production-safe values
     */
    /**
     * Perform the Error Reporting health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        // Get error reporting level from Global Configuration
        $errorReporting = Factory::getApplication()->get('error_reporting', 'default');

        // Maximum or development mode - exposes detailed error information
        if ($errorReporting === 'maximum' || $errorReporting === 'development') {
            return $this->warning(
                'Error reporting is set to maximum/development. This may expose sensitive information in production.',
            );
        }

        // None or simple - appropriate for production environments
        if ($errorReporting === 'none' || $errorReporting === 'simple') {
            return $this->good('Error reporting is appropriately configured for production.');
        }

        // Other values - report current setting
        return $this->good(sprintf('Error reporting is set to: %s', $errorReporting));
    }
}

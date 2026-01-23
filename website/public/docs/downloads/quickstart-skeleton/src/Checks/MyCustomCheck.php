<?php

declare(strict_types=1);

/**
 * @package     Joomla.Plugin
 * @subpackage  HealthChecker.MyPlugin
 *
 * @copyright   (C) 2026 Your Company
 * @license     GNU General Public License version 2 or later
 */

namespace YourCompany\Plugin\HealthChecker\MyPlugin\Checks;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

/**
 * My Custom Health Check
 *
 * [Brief description of what this check does]
 *
 * WHY THIS CHECK IS IMPORTANT:
 * [Explain why this check matters, what risks it mitigates, benefits it provides]
 *
 * RESULT MEANINGS:
 *
 * GOOD: [What conditions produce a good status]
 *
 * WARNING: [What triggers a warning and how to resolve it]
 *
 * CRITICAL: [What triggers critical status and immediate actions needed]
 *           [Or: "This check does not return critical status."]
 *
 * @since 1.0.0
 */
final class MyCustomCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * Format: {provider}.{check_name}
     * Use lowercase, numbers, and underscores only.
     *
     * @return string
     * @since  1.0.0
     */
    public function getSlug(): string
    {
        return 'myplugin.my_custom_check';
    }

    /**
     * Get the category this check belongs to.
     *
     * Use a standard category (system, database, security, users, extensions,
     * performance, seo, content) or your custom category slug.
     *
     * @return string
     * @since  1.0.0
     */
    public function getCategory(): string
    {
        return 'mycategory'; // Or use a standard category like 'system'
    }

    /**
     * Get the provider slug for this check.
     *
     * @return string
     * @since  1.0.0
     */
    public function getProvider(): string
    {
        return 'myplugin';
    }

    /**
     * Perform the actual health check logic.
     *
     * This method is wrapped in automatic error handling by AbstractHealthCheck.
     * Any thrown exceptions will be caught and returned as WARNING results.
     *
     * Use helper methods to return results:
     * - $this->critical('Description') - Site broken or data at risk
     * - $this->warning('Description')  - Should fix but site still works
     * - $this->good('Description')     - Everything optimal
     *
     * @return HealthCheckResult
     * @since  1.0.0
     */
    protected function performCheck(): HealthCheckResult
    {
        // Example: Check if a configuration value is set
        // $db = $this->requireDatabase(); // Get database with null-safety check

        // Perform your check logic here
        $somethingIsGood = true; // Replace with actual check logic

        if (!$somethingIsGood) {
            return $this->critical('Critical problem detected. Immediate action required.');
        }

        $hasWarning = false; // Replace with actual check logic

        if ($hasWarning) {
            return $this->warning('Minor issue detected. Consider fixing.');
        }

        return $this->good('Everything looks great!');
    }
}

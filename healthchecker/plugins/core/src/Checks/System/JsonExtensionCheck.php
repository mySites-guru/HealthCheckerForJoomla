<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * JSON Extension Health Check
 *
 * This check verifies that the PHP JSON extension is loaded and available.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The JSON extension is essential for Joomla's operation. It is used for:
 * - API requests and responses (RESTful web services)
 * - Configuration file parsing and storage
 * - AJAX communication between frontend and backend
 * - Extension manifest processing
 * - Session data serialization
 * Without JSON support, Joomla cannot function at all.
 *
 * RESULT MEANINGS:
 *
 * GOOD: JSON extension is loaded and operational.
 *
 * CRITICAL: JSON extension is not available. Joomla will not work without it.
 *           Contact your hosting provider to enable the JSON extension.
 *
 * Note: This check does not produce WARNING results as JSON is a hard requirement.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class JsonExtensionCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.json_extension'
     */
    public function getSlug(): string
    {
        return 'system.json_extension';
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
     * Performs the JSON extension availability check.
     *
     * Verifies that the PHP JSON extension is loaded. This extension is essential
     * for Joomla's operation and is used for API requests/responses, configuration
     * file parsing, AJAX communication, extension manifest processing, and session
     * data serialization. Without JSON support, Joomla cannot function at all.
     *
     * @return HealthCheckResult Good status if JSON extension is loaded,
     *                            Critical status if JSON extension is not available
     */
    /**
     * Perform the Json Extension health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        // JSON is a hard requirement for Joomla
        if (! extension_loaded('json')) {
            return $this->critical('JSON extension is not loaded. This is required for Joomla.');
        }

        return $this->good('JSON extension is loaded.');
    }
}

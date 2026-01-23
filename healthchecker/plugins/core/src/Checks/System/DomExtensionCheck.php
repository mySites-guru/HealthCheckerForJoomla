<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * DOM Extension Health Check
 *
 * This check verifies that the PHP DOM (Document Object Model) extension is loaded.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The DOM extension provides essential XML/HTML document manipulation:
 * - Parsing and modifying HTML content in articles and modules
 * - Processing XML configuration and manifest files
 * - Email template rendering with HTML manipulation
 * - Web services integration requiring XML parsing
 * - SEF URL processing and content filtering
 * Joomla requires DOM for proper content handling and extension management.
 *
 * RESULT MEANINGS:
 *
 * GOOD: DOM extension is loaded and operational.
 *
 * CRITICAL: DOM extension is not available. Content processing, email templates,
 *           and extension installation will fail. Contact your hosting provider
 *           to enable the DOM extension.
 *
 * Note: This check does not produce WARNING results as DOM is a hard requirement.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class DomExtensionCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.dom_extension'
     */
    public function getSlug(): string
    {
        return 'system.dom_extension';
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
     * Performs the DOM extension availability check.
     *
     * Verifies that the PHP DOM (Document Object Model) extension is loaded. This
     * extension provides essential XML/HTML document manipulation for parsing and
     * modifying HTML content in articles/modules, processing XML configuration and
     * manifest files, email template rendering, web services integration, and SEF URL
     * processing. Joomla requires DOM for proper content handling and extension management.
     *
     * @return HealthCheckResult Good status if DOM extension is loaded,
     *                            Critical status if DOM extension is not available
     */
    /**
     * Perform the Dom Extension health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        // DOM is a hard requirement for Joomla
        if (! extension_loaded('dom')) {
            return $this->critical('DOM extension is not loaded. This is required for Joomla.');
        }

        return $this->good('DOM extension is loaded.');
    }
}

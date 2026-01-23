<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * SimpleXML Extension Health Check
 *
 * This check verifies that the PHP SimpleXML extension is loaded and available.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The SimpleXML extension is critical for Joomla's XML processing needs:
 * - Parsing extension manifest files (*.xml)
 * - Reading and writing configuration files
 * - Processing language files and translations
 * - Handling RSS/Atom feeds
 * - Installing, updating, and managing extensions
 * Without SimpleXML, Joomla cannot install extensions or read configuration properly.
 *
 * RESULT MEANINGS:
 *
 * GOOD: SimpleXML extension is loaded and operational.
 *
 * CRITICAL: SimpleXML extension is not available. Joomla's extension system
 *           and configuration management will fail. Contact your hosting
 *           provider to enable the SimpleXML extension.
 *
 * Note: This check does not produce WARNING results as SimpleXML is a hard requirement.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class SimpleXmlExtensionCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.simplexml_extension'
     */
    public function getSlug(): string
    {
        return 'system.simplexml_extension';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'system'
     */
    public function getCategory(): string
    {
        return 'system';
    }

    /**
     * Verify that the SimpleXML extension is loaded.
     *
     * Checks if the simplexml PHP extension is available. This extension is critical
     * for Joomla's core functionality as it's used to parse extension manifest files,
     * configuration files, language files, RSS/Atom feeds, and all XML-based operations.
     * Without SimpleXML, Joomla cannot install or update extensions, and basic
     * configuration reading will fail.
     *
     * @return HealthCheckResult CRITICAL if simplexml is not loaded, GOOD if available
     */
    /**
     * Perform the Simple Xml Extension health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        if (! extension_loaded('simplexml')) {
            return $this->critical('SimpleXML extension is not loaded. This is required for Joomla.');
        }

        return $this->good('SimpleXML extension is loaded.');
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Fileinfo Extension Health Check
 *
 * This check verifies that the PHP Fileinfo extension is loaded for MIME type detection.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The Fileinfo extension provides reliable MIME type detection for uploaded files:
 * - Validating uploaded file types in the Media Manager
 * - Preventing malicious file uploads disguised with fake extensions
 * - Correctly serving files with proper Content-Type headers
 * - Security checks for allowed file types
 * - Email attachment handling
 * Without Fileinfo, Joomla must rely on less reliable methods like file extensions,
 * which can be spoofed by attackers.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Fileinfo extension is loaded. MIME type detection will accurately
 *       identify file types regardless of extension, improving security.
 *
 * WARNING: Fileinfo extension is not available. File type detection will fall
 *          back to checking file extensions, which is less secure as extensions
 *          can be faked. Consider enabling fileinfo for better upload security.
 *
 * Note: This check does not produce CRITICAL results as Joomla can function
 *       with fallback detection, though security is somewhat reduced.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class FileinfoExtensionCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.fileinfo_extension'
     */
    public function getSlug(): string
    {
        return 'system.fileinfo_extension';
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
     * Verify that the Fileinfo extension is loaded.
     *
     * Checks if the fileinfo PHP extension is available. This extension provides
     * reliable MIME type detection for uploaded files by analyzing file contents
     * rather than relying solely on file extensions. This is crucial for security
     * as it prevents malicious files from being uploaded with fake extensions.
     * Without Fileinfo, Joomla falls back to less secure extension-based detection.
     *
     * @return HealthCheckResult WARNING if fileinfo is not loaded, GOOD if available
     */
    protected function performCheck(): HealthCheckResult
    {
        if (! extension_loaded('fileinfo')) {
            return $this->warning('Fileinfo extension is not loaded. MIME type detection may not work correctly.');
        }

        return $this->good('Fileinfo extension is loaded.');
    }
}

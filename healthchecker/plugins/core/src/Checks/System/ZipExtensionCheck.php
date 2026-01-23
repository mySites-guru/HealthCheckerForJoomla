<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Zip Extension Health Check
 *
 * This check verifies that the PHP Zip extension is loaded for archive handling.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The Zip extension is critical for Joomla's extension management system:
 * - Installing extensions, templates, and language packs (all distributed as .zip)
 * - Joomla core updates (downloaded as zip archives)
 * - Backup and restore operations
 * - Export/import functionality in various extensions
 * - Media Manager bulk operations
 * Without Zip support, you cannot install or update any extensions or Joomla itself.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Zip extension is loaded. Extension installation and updates will work.
 *
 * CRITICAL: Zip extension is not available. You will not be able to install
 *           extensions, update Joomla, or perform backup operations.
 *           Contact your hosting provider to enable the zip extension.
 *
 * Note: This check does not produce WARNING results as Zip is essential for
 *       extension management.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ZipExtensionCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.zip_extension'
     */
    public function getSlug(): string
    {
        return 'system.zip_extension';
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
     * Performs the Zip extension availability check.
     *
     * Verifies that the PHP Zip extension is loaded. This extension is critical for
     * Joomla's extension management system, enabling installation of extensions/templates/
     * language packs, Joomla core updates, backup/restore operations, export/import
     * functionality, and Media Manager bulk operations. Without Zip support, you cannot
     * install or update any extensions or Joomla itself.
     *
     * @return HealthCheckResult Good status if Zip extension is loaded,
     *                            Critical status if Zip extension is not available
     */
    /**
     * Perform the Zip Extension health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        // Zip is essential for extension management and updates
        if (! extension_loaded('zip')) {
            return $this->critical('Zip extension is not loaded. Extension installation will not work.');
        }

        return $this->good('Zip extension is loaded.');
    }
}

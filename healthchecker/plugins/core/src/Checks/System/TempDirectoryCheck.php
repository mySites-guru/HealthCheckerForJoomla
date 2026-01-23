<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Temp Directory Health Check
 *
 * This check verifies that Joomla's configured temporary directory exists and is writable.
 * The temp directory is used for file uploads, extension installations, cache operations,
 * and various system operations that require temporary file storage.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Without a functional temp directory, Joomla cannot install extensions, process file
 * uploads, or perform many administrative operations. A missing or non-writable temp
 * directory can cause silent failures that are difficult to diagnose.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The configured temp directory exists and PHP has write permissions to it.
 * All temporary file operations should work correctly.
 *
 * WARNING: This check does not produce warning results.
 *
 * CRITICAL: Either the temp directory does not exist on disk, or PHP cannot write to it.
 * Extension installations, file uploads, and other operations will fail until resolved.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class TempDirectoryCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.temp_directory'
     */
    public function getSlug(): string
    {
        return 'system.temp_directory';
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
     * Perform the temp directory health check.
     *
     * Validates that Joomla's configured temp directory exists on disk and is
     * writable by PHP. The temp directory is critical for file uploads, extension
     * installations, and various cache operations.
     *
     * @return HealthCheckResult Critical if directory missing or not writable, Good otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        // Get configured temp path from Joomla config, fallback to JPATH_ROOT/tmp
        $config = Factory::getApplication()->get('tmp_path', JPATH_ROOT . '/tmp');

        // Check if directory exists on filesystem
        if (! is_dir($config)) {
            return $this->critical(sprintf('Temp directory does not exist: %s', $config));
        }

        // Verify PHP has write permissions to the directory
        if (! is_writable($config)) {
            return $this->critical(sprintf('Temp directory is not writable: %s', $config));
        }

        return $this->good('Temp directory exists and is writable.');
    }
}

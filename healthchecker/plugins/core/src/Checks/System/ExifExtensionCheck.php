<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * EXIF Extension Health Check
 *
 * This check verifies that PHP's EXIF extension is installed, which enables reading
 * metadata embedded in JPEG and TIFF images. This metadata includes camera settings,
 * GPS coordinates, timestamps, orientation, and other photographic information.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * While not strictly required for Joomla core, the EXIF extension enables advanced
 * media handling features. It allows automatic image rotation based on orientation
 * metadata, extraction of photo location data for mapping, and proper handling of
 * camera-produced images. Some media extensions rely on EXIF for full functionality.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The EXIF extension is installed and the exif_read_data() function is available.
 * Image metadata reading is fully supported.
 *
 * WARNING: The EXIF extension is not installed. Image metadata features will not be
 * available. This is not critical for basic Joomla operation but may affect media
 * handling extensions or cause images to display with incorrect orientation.
 *
 * CRITICAL: This check does not produce critical results as EXIF is optional.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ExifExtensionCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.exif_extension'
     */
    public function getSlug(): string
    {
        return 'system.exif_extension';
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
     * Verify that the EXIF extension is available.
     *
     * Checks if the exif_read_data() function exists, which indicates the EXIF
     * extension is loaded. This extension allows reading metadata from JPEG and
     * TIFF images including camera settings, GPS coordinates, and orientation data.
     * While not strictly required for Joomla core, it enables proper image rotation
     * and advanced media handling features.
     *
     * @return HealthCheckResult WARNING if EXIF is not available, GOOD if present
     */
    protected function performCheck(): HealthCheckResult
    {
        if (! \function_exists('exif_read_data')) {
            return $this->warning('EXIF extension is not installed. Image metadata reading will not be available.');
        }

        return $this->good('EXIF extension is installed for image metadata support.');
    }
}

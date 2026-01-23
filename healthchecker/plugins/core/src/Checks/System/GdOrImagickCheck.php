<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * GD or Imagick Extension Health Check
 *
 * This check verifies that at least one image processing library (GD or Imagick) is available.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Image processing extensions are essential for media management in Joomla:
 * - Generating thumbnails for the Media Manager
 * - Resizing uploaded images automatically
 * - Creating responsive image variants
 * - CAPTCHA image generation for forms
 * - Avatar and profile picture processing
 * At least one of these extensions must be available for image operations.
 * Imagick generally offers better quality and more format support than GD.
 *
 * RESULT MEANINGS:
 *
 * GOOD: At least one image processing extension is loaded.
 *       - Having both GD and Imagick provides maximum compatibility.
 *       - Imagick alone is excellent for high-quality image processing.
 *       - GD alone is sufficient for basic image operations.
 *
 * CRITICAL: Neither GD nor Imagick is available. Image uploads, thumbnails,
 *           and CAPTCHA will not work. Contact your hosting provider to
 *           enable at least the GD extension (or Imagick for better quality).
 *
 * Note: This check does not produce WARNING results - either image processing
 *       works or it does not.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class GdOrImagickCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.gd_or_imagick'
     */
    public function getSlug(): string
    {
        return 'system.gd_or_imagick';
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
     * Verify that at least one image processing extension is available.
     *
     * Checks if either the GD or Imagick PHP extension is loaded. At least one
     * of these extensions is required for Joomla's media manager to generate
     * thumbnails, resize images, create responsive image variants, and generate
     * CAPTCHA images. Imagick generally provides better quality and format support,
     * but GD is sufficient for basic operations.
     *
     * @return HealthCheckResult CRITICAL if neither extension is available,
     *                           GOOD if at least one is loaded (reports which ones)
     */
    protected function performCheck(): HealthCheckResult
    {
        $hasGd = extension_loaded('gd');
        $hasImagick = extension_loaded('imagick');

        // Neither extension available - image processing will fail
        if (! $hasGd && ! $hasImagick) {
            return $this->critical('Neither GD nor Imagick extension is loaded. Image processing will not work.');
        }

        // Build list of available extensions
        $loaded = [];
        if ($hasGd) {
            $loaded[] = 'GD';
        }

        if ($hasImagick) {
            $loaded[] = 'Imagick';
        }

        return $this->good(sprintf('%s extension(s) loaded for image processing.', implode(' and ', $loaded)));
    }
}

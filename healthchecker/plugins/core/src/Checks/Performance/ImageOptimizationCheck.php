<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Image Optimization Health Check
 *
 * This check scans the /images directory for image files larger than 500KB
 * that may be impacting page load performance.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Large, unoptimized images are one of the most common causes of slow page loads.
 * Images over 500KB can significantly impact performance, especially on mobile
 * devices or slower connections. Optimizing images (compressing, resizing, using
 * modern formats like WebP) can dramatically improve page load times and Core Web
 * Vitals scores.
 *
 * RESULT MEANINGS:
 *
 * GOOD: No images larger than 500KB were found in the images directory.
 * Your images are appropriately sized for web delivery.
 *
 * WARNING: One or more images larger than 500KB were detected. Consider
 * compressing these images, resizing them to appropriate dimensions, or
 * converting them to more efficient formats like WebP.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ImageOptimizationCheck extends AbstractHealthCheck
{
    /**
     * Maximum recommended file size for images in bytes (500KB).
     *
     * Images exceeding this size are considered oversized and may negatively
     * impact page load performance, especially on mobile devices or slower
     * connections.
     */
    private const MAX_RECOMMENDED_SIZE = 500 * 1024;

    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'performance.image_optimization'
     */
    public function getSlug(): string
    {
        return 'performance.image_optimization';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category identifier 'performance'
     */
    public function getCategory(): string
    {
        return 'performance';
    }

    /**
     * Perform the image optimization health check.
     *
     * This method scans the /images directory for image files larger than 500KB
     * that may be impacting page load performance. The scan is limited to the
     * first 1000 files to prevent excessive processing time on large sites.
     *
     * The check examines common image formats (JPG, PNG, GIF, WebP) and reports:
     * - GOOD: No oversized images found
     * - WARNING: One or more images larger than 500KB detected
     *
     * @return HealthCheckResult The result indicating image optimization status
     */
    /**
     * Perform the Image Optimization health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $imagesPath = JPATH_ROOT . '/images';

        // If images directory doesn't exist, nothing to check
        if (! is_dir($imagesPath)) {
            return $this->good('Images directory not found.');
        }

        // Scan for large images with a limit to prevent excessive processing
        [$largeImages, $limitReached] = $this->findLargeImages($imagesPath);
        $count = count($largeImages);

        // No oversized images found - optimal state
        if ($count === 0) {
            $message = $limitReached
                ? 'No oversized images detected in first 1000 files scanned.'
                : 'No oversized images detected in the images directory.';
            return $this->good($message);
        }

        // Scan was limited - inform user there may be more
        if ($limitReached) {
            return $this->warning(
                sprintf(
                    'Found %d images larger than 500KB (scan limited to 1000 files). Full site may have more oversized images.',
                    $count,
                ),
            );
        }

        // Many oversized images found - provide count only
        if ($count > 10) {
            return $this->warning(
                sprintf(
                    'Found %d images larger than 500KB. Consider optimizing images for better page load times.',
                    $count,
                ),
            );
        }

        // Few oversized images - provide specific file paths
        return $this->warning(
            sprintf(
                'Found %d image(s) larger than 500KB. Consider optimizing: %s',
                $count,
                implode(', ', array_slice($largeImages, 0, 5)),
            ),
        );
    }

    /**
     * Recursively scan directory for image files exceeding the size threshold.
     *
     * This method iterates through the images directory and identifies all image
     * files (JPG, JPEG, PNG, GIF, WebP) that exceed MAX_RECOMMENDED_SIZE. To
     * prevent excessive processing time on sites with thousands of images, the
     * scan is limited to the first 1000 files encountered.
     *
     * @param string $directory The absolute path to the directory to scan
     *
     * @return array{0: array<string>, 1: bool} Tuple containing:
     *                                          - Array of relative paths to oversized images
     *                                          - Boolean indicating if the 1000 file limit was reached
     */
    private function findLargeImages(string $directory): array
    {
        $largeImages = [];
        // Image file extensions to check
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxFiles = 1000; // Limit to prevent excessive scanning on large sites
        $scanned = 0;

        // Recursively iterate through all files in the directory tree
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY,
        );

        $limitReached = false;

        foreach ($iterator as $file) {
            // Enforce scan limit to prevent performance issues
            if ($scanned++ >= $maxFiles) {
                $limitReached = true;
                break;
            }

            // Skip non-files (directories, symlinks, etc.)
            if (! $file->isFile()) {
                continue;
            }

            $extension = strtolower((string) $file->getExtension());

            // Only process image file types
            if (! in_array($extension, $extensions, true)) {
                continue;
            }

            // Check if file size exceeds recommended maximum
            if ($file->getSize() > self::MAX_RECOMMENDED_SIZE) {
                // Store relative path for cleaner reporting
                $largeImages[] = str_replace(JPATH_ROOT . '/', '', $file->getPathname());
            }
        }

        return [$largeImages, $limitReached];
    }
}

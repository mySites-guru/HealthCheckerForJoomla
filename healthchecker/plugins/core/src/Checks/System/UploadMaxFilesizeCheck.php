<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Upload Max Filesize Health Check
 *
 * This check verifies the maximum file size that can be uploaded via PHP.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * upload_max_filesize controls the largest single file that can be uploaded.
 * This directly impacts:
 * - Media uploads (images, videos, PDFs)
 * - Extension package installations
 * - Backup file restorations
 * - Document attachments
 *
 * NOTE: upload_max_filesize must be <= post_max_size to be effective.
 *
 * RESULT MEANINGS:
 *
 * GOOD: upload_max_filesize is 10M or higher and <= post_max_size.
 *       Users can upload reasonable-sized media and install most extensions.
 *
 * WARNING: upload_max_filesize is between 2M-10M, or exceeds post_max_size.
 *          Small files work but high-res images and extension packages
 *          may fail to upload. If exceeding post_max_size, uploads are
 *          effectively limited to the smaller value.
 *
 * CRITICAL: upload_max_filesize is below 2M.
 *           Most image uploads will fail. Extension installations via
 *           upload are nearly impossible.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class UploadMaxFilesizeCheck extends AbstractHealthCheck
{
    /**
     * Minimum acceptable upload filesize in bytes (2M).
     */
    private const MINIMUM_BYTES = 2 * 1024 * 1024; // 2M

    /**
     * Recommended upload filesize in bytes (10M).
     */
    private const RECOMMENDED_BYTES = 10 * 1024 * 1024; // 10M

    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'system.upload_max_filesize'
     */
    public function getSlug(): string
    {
        return 'system.upload_max_filesize';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category identifier 'system'
     */
    public function getCategory(): string
    {
        return 'system';
    }

    /**
     * Perform the upload_max_filesize check.
     *
     * Verifies that PHP allows sufficiently large file uploads for media and extensions.
     * Also validates that upload_max_filesize doesn't exceed post_max_size, as the
     * effective limit is the smaller of the two values.
     *
     * @return HealthCheckResult Critical if below 2M, Warning if below 10M or exceeds post_max_size, Good otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');

        if ($uploadMaxFilesize === false || $postMaxSize === false) {
            return $this->warning('Unable to retrieve upload_max_filesize or post_max_size settings.');
        }

        $uploadBytes = $this->convertToBytes($uploadMaxFilesize);
        $postBytes = $this->convertToBytes($postMaxSize);

        if ($uploadBytes < self::MINIMUM_BYTES) {
            return $this->critical(
                sprintf('upload_max_filesize (%s) is below the minimum required 2M.', $uploadMaxFilesize),
            );
        }

        if ($uploadBytes > $postBytes) {
            return $this->warning(
                sprintf(
                    'upload_max_filesize (%s) exceeds post_max_size (%s). Uploads will be limited by post_max_size.',
                    $uploadMaxFilesize,
                    $postMaxSize,
                ),
            );
        }

        if ($uploadBytes < self::RECOMMENDED_BYTES) {
            return $this->warning(
                sprintf('upload_max_filesize (%s) is below the recommended 10M.', $uploadMaxFilesize),
            );
        }

        return $this->good(sprintf('upload_max_filesize (%s) meets requirements.', $uploadMaxFilesize));
    }

    /**
     * Convert PHP shorthand notation to bytes.
     *
     * Converts values like "10M", "2G", "512K" to their byte equivalents.
     * Handles the following suffixes:
     * - 'G' or 'g': Gigabytes (multiply by 1024^3)
     * - 'M' or 'm': Megabytes (multiply by 1024^2)
     * - 'K' or 'k': Kilobytes (multiply by 1024)
     * - No suffix: Bytes (no conversion)
     *
     * @param string $value The value to convert (e.g., "10M", "2G")
     *
     * @return int The value in bytes, or 0 if empty
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '0') {
            return 0;
        }

        $last = strtolower($value[strlen($value) - 1]);
        $numericValue = (int) $value;

        // Apply multiplier based on suffix
        $numericValue = match ($last) {
            'g' => $numericValue * 1024 * 1024 * 1024,
            'm' => $numericValue * 1024 * 1024,
            'k' => $numericValue * 1024,
            default => $numericValue,
        };

        return $numericValue;
    }
}

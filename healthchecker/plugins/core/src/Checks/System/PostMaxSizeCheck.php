<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Post Max Size Health Check
 *
 * This check verifies that PHP can accept sufficiently large POST requests.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * post_max_size limits the total size of POST data including file uploads and form fields.
 * This setting affects:
 * - File uploads (must be >= upload_max_filesize)
 * - Large form submissions (articles with images, page builders)
 * - Extension uploads and installations
 * - Backup restoration via admin
 *
 * RESULT MEANINGS:
 *
 * GOOD: post_max_size is 32M or higher.
 *       Adequate for most operations including extension installations
 *       and content with embedded images.
 *
 * WARNING: post_max_size is between 8M and 32M.
 *          Basic operations work but large extension packages or
 *          content-heavy articles may fail to save.
 *
 * CRITICAL: post_max_size is below 8M.
 *           Many operations will fail. Extension installations and
 *           articles with images cannot be saved reliably.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class PostMaxSizeCheck extends AbstractHealthCheck
{
    /**
     * Minimum acceptable POST size in bytes (8M).
     */
    private const MINIMUM_BYTES = 8 * 1024 * 1024; // 8M

    /**
     * Recommended POST size in bytes (32M).
     */
    private const RECOMMENDED_BYTES = 32 * 1024 * 1024; // 32M

    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'system.post_max_size'
     */
    public function getSlug(): string
    {
        return 'system.post_max_size';
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
     * Perform the post_max_size check.
     *
     * Verifies that PHP can accept sufficiently large POST requests including file uploads.
     * This value must be >= upload_max_filesize to be effective. Affects extension
     * installations, file uploads, and form submissions with embedded media.
     *
     * @return HealthCheckResult Critical if below 8M, Warning if below 32M, Good otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $postMaxSize = ini_get('post_max_size');
        if ($postMaxSize === false) {
            return $this->warning('Unable to retrieve post_max_size setting.');
        }

        $bytes = $this->convertToBytes($postMaxSize);

        if ($bytes < self::MINIMUM_BYTES) {
            return $this->critical(sprintf('post_max_size (%s) is below the minimum required 8M.', $postMaxSize));
        }

        if ($bytes < self::RECOMMENDED_BYTES) {
            return $this->warning(sprintf('post_max_size (%s) is below the recommended 32M.', $postMaxSize));
        }

        return $this->good(sprintf('post_max_size (%s) meets requirements.', $postMaxSize));
    }

    /**
     * Convert PHP shorthand notation to bytes.
     *
     * Converts values like "32M", "1G", "512K" to their byte equivalents.
     * Handles the following suffixes:
     * - 'G' or 'g': Gigabytes (multiply by 1024^3)
     * - 'M' or 'm': Megabytes (multiply by 1024^2)
     * - 'K' or 'k': Kilobytes (multiply by 1024)
     * - No suffix: Bytes (no conversion)
     *
     * @param string $value The value to convert (e.g., "32M", "1G")
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

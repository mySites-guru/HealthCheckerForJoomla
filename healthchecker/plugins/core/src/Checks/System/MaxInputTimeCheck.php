<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Max Input Time Health Check
 *
 * This check verifies that PHP has sufficient time to parse incoming request data.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * max_input_time limits how long PHP spends receiving and parsing POST data and file uploads.
 * This is separate from max_execution_time and affects:
 * - Large file uploads (images, documents, backups)
 * - Form submissions with many fields
 * - Bulk data imports via web interface
 *
 * RESULT MEANINGS:
 *
 * GOOD: Input time is 60 seconds or more (or unlimited with -1).
 *       File uploads and large form submissions will process reliably.
 *
 * WARNING: Input time is below 60 seconds.
 *          Large file uploads may fail before the file is fully received.
 *          Consider increasing this value if users upload media frequently.
 *
 * CRITICAL: This check does not return critical status as low values cause
 *           upload failures rather than security issues.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class MaxInputTimeCheck extends AbstractHealthCheck
{
    /**
     * Minimum recommended input time in seconds.
     */
    private const MINIMUM_SECONDS = 60;

    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'system.max_input_time'
     */
    public function getSlug(): string
    {
        return 'system.max_input_time';
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
     * Perform the max input time check.
     *
     * Verifies that PHP has sufficient time to parse incoming request data and file uploads.
     * This is independent of max_execution_time and specifically affects data reception.
     * Values of -1 or 0 indicate unlimited input time.
     *
     * @return HealthCheckResult Warning if below 60s, Good otherwise (no critical status)
     */
    protected function performCheck(): HealthCheckResult
    {
        $maxInputTime = (int) ini_get('max_input_time');

        if ($maxInputTime === -1 || $maxInputTime === 0) {
            return $this->good('Max input time is unlimited.');
        }

        if ($maxInputTime < self::MINIMUM_SECONDS) {
            return $this->warning(
                sprintf(
                    'Max input time (%ds) may cause issues with large file uploads. Recommended: %ds or unlimited (-1).',
                    $maxInputTime,
                    self::MINIMUM_SECONDS,
                ),
            );
        }

        return $this->good(sprintf('Max input time (%ds) is adequate.', $maxInputTime));
    }
}

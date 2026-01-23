<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Output Buffering Health Check
 *
 * This check reports the PHP output buffering configuration status.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Output buffering controls how PHP sends data to the browser. It affects:
 * - Memory usage (buffered content is held in memory)
 * - "Headers already sent" errors
 * - Streaming/download functionality
 * - Gzip compression compatibility
 *
 * This is informational - both enabled and disabled states are valid
 * depending on your server configuration.
 *
 * RESULT MEANINGS:
 *
 * GOOD: This check always returns good as both states are acceptable.
 *       - Disabled (Off/0): Recommended for performance, content streams immediately
 *       - Enabled (On/1): Content is buffered before sending, can help with
 *         header modifications but uses more memory
 *       - Numeric value: Specific buffer size in bytes
 *
 * WARNING: This check does not return warning status.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class OutputBufferingCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'system.output_buffering'
     */
    public function getSlug(): string
    {
        return 'system.output_buffering';
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
     * Perform the output buffering check.
     *
     * Reports the current output buffering configuration. This is informational only
     * as both enabled and disabled states are valid depending on server setup.
     * Output buffering affects memory usage, header modification, and content streaming.
     *
     * Possible values:
     * - Off/0: Disabled (recommended for performance)
     * - On/1: Enabled (buffers all output)
     * - Numeric: Buffer size in bytes
     *
     * @return HealthCheckResult Always returns Good as this is informational only
     */
    /**
     * Perform the Output Buffering health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $outputBuffering = ini_get('output_buffering');

        // Check if output buffering is disabled
        if (in_array($outputBuffering, ['', '0', 'Off'], true)) {
            return $this->good('Output buffering is disabled (recommended for performance).');
        }

        // Check if output buffering is enabled without specific size
        if ($outputBuffering === '1' || $outputBuffering === 'On') {
            return $this->good('Output buffering is enabled.');
        }

        // Output buffering is enabled with specific buffer size
        return $this->good(sprintf('Output buffering is set to %s bytes.', $outputBuffering));
    }
}

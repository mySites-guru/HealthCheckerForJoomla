<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Session Save Path Health Check
 *
 * This check verifies that PHP's session save path exists and is writable. When Joomla
 * uses file-based sessions (the default), PHP stores session data in files within this
 * directory. Without a valid session save path, user logins and sessions will fail.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Sessions are fundamental to Joomla's operation - they maintain user login state,
 * store form tokens for security, and preserve temporary data between requests. If
 * the session save path is missing or not writable, users cannot log in, forms fail
 * to submit, and the administrator becomes inaccessible.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The session save path exists and PHP can write to it. File-based sessions
 * will function correctly.
 *
 * WARNING: This check does not produce warning results.
 *
 * CRITICAL: Either the session save path directory does not exist, or PHP cannot
 * write to it. This will cause immediate session failures - users cannot log in
 * and form submissions will fail. Requires immediate resolution.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class SessionSavePathCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.session_save_path'
     */
    public function getSlug(): string
    {
        return 'system.session_save_path';
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
     * Perform the session save path health check.
     *
     * Validates that PHP's session save path exists and is writable. This is critical
     * for file-based sessions (Joomla's default) as it determines where session data
     * files are stored. Without a valid path, user logins and sessions will fail.
     *
     * @return HealthCheckResult Critical if path missing or not writable, Good otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        // Get PHP's configured session save path
        $savePath = session_save_path();

        // If session_save_path returns empty or false, fallback to system temp directory
        // This matches PHP's default behavior when save_path is not configured
        if (in_array($savePath, ['', '0', false], true)) {
            $savePath = sys_get_temp_dir();
        }

        // Verify directory exists on filesystem
        if (! is_dir($savePath)) {
            return $this->critical(sprintf('Session save path does not exist: %s', $savePath));
        }

        // Verify PHP has write permissions to store session files
        if (! is_writable($savePath)) {
            return $this->critical(sprintf('Session save path is not writable: %s', $savePath));
        }

        return $this->good(sprintf('Session save path is writable: %s', $savePath));
    }
}

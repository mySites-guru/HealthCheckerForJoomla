<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * PHP Version Health Check
 *
 * This check verifies that the server is running a compatible and secure version of PHP.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * PHP is the foundation of Joomla. Running an outdated version can lead to:
 * - Security vulnerabilities (unpatched exploits)
 * - Compatibility issues with Joomla core and extensions
 * - Missing performance improvements and language features
 * - Loss of official PHP security support
 *
 * RESULT MEANINGS:
 *
 * GOOD: PHP version meets or exceeds the recommended version (8.2+).
 *       Your server is running an optimal PHP version with full support.
 *
 * WARNING: PHP version is above minimum (8.1) but below recommended (8.2).
 *          The site will function but you're missing performance improvements
 *          and newer security patches. Plan to upgrade soon.
 *
 * CRITICAL: PHP version is below minimum required (8.1).
 *           Joomla 5+ requires PHP 8.1 minimum. Your site may malfunction
 *           or be vulnerable to known security exploits. Upgrade immediately.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class PhpVersionCheck extends AbstractHealthCheck
{
    /**
     * Minimum required PHP version for Joomla 5+.
     */
    private const MINIMUM_VERSION = '8.1.0';

    /**
     * Recommended PHP version for optimal performance and security.
     */
    private const RECOMMENDED_VERSION = '8.2.0';

    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'system.php_version'
     */
    public function getSlug(): string
    {
        return 'system.php_version';
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
     * Perform the PHP version check.
     *
     * Validates that the current PHP version meets minimum requirements (8.1+)
     * and recommends upgrading to 8.2+ for optimal security and performance.
     *
     * @return HealthCheckResult Critical if below 8.1, Warning if below 8.2, Good otherwise
     */
    protected function performCheck(): HealthCheckResult
    {
        $currentVersion = PHP_VERSION;

        if (version_compare($currentVersion, self::MINIMUM_VERSION, '<')) {
            return $this->critical(
                sprintf(
                    'PHP %s is below the minimum required version %s for Joomla 5+.',
                    $currentVersion,
                    self::MINIMUM_VERSION,
                ),
            );
        }

        if (version_compare($currentVersion, self::RECOMMENDED_VERSION, '<')) {
            return $this->warning(
                sprintf(
                    'PHP %s is supported but %s or later is recommended for best performance and security.',
                    $currentVersion,
                    self::RECOMMENDED_VERSION,
                ),
            );
        }

        return $this->good(sprintf('PHP %s meets all requirements.', $currentVersion));
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * configuration.php Permissions Health Check
 *
 * This check verifies that the configuration.php file has secure file permissions.
 * This file contains database credentials, secret keys, and other sensitive
 * configuration data.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The configuration.php file contains your database password, secret key, SMTP
 * credentials, and FTP passwords. If this file is world-readable or world-writable,
 * other users on shared hosting or attackers exploiting other vulnerabilities could
 * read or modify your credentials.
 *
 * RESULT MEANINGS:
 *
 * GOOD: File permissions are restrictive (e.g., 640 or 600), meaning only the owner
 *       and possibly the web server group can read the file, and it is not writable
 *       by others.
 *
 * WARNING: The file is world-readable (permissions like 644). Other users on the
 *          server may be able to read your credentials. Set permissions to 640 or 600.
 *
 * CRITICAL: The file is world-writable (permissions like 666 or 777). Anyone can
 *           modify your configuration. This is a severe security risk. Fix permissions
 *           immediately.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ConfigurationPhpPermissionsCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug for this health check
     *
     * @return string The check slug in format 'security.configuration_php_permissions'
     */
    public function getSlug(): string
    {
        return 'security.configuration_php_permissions';
    }

    /**
     * Get the category this check belongs to
     *
     * @return string The category slug 'security'
     */
    public function getCategory(): string
    {
        return 'security';
    }

    /**
     * Perform the configuration.php file permissions health check
     *
     * Verifies that the configuration.php file has secure Unix file permissions.
     * This file contains sensitive data including database credentials, secret keys,
     * SMTP passwords, and FTP credentials.
     *
     * Security considerations:
     * - World-writable (0x0002 bit set): Anyone can modify your configuration - CRITICAL
     * - World-readable (0x0004 bit set): Other users can read your credentials - WARNING
     * - Recommended permissions: 640 (owner read/write, group read) or 600 (owner read/write only)
     * - Particularly important on shared hosting where multiple users share the same server
     * - Using bitwise AND to check specific permission bits (POSIX standard)
     *
     * Permission bits (octal):
     * - 0x0001 (1): World execute
     * - 0x0002 (2): World write
     * - 0x0004 (4): World read
     *
     * @return HealthCheckResult Result indicating file permission security level
     */
    /**
     * Perform the Configuration Php Permissions health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $configPath = JPATH_ROOT . '/configuration.php';

        if (! file_exists($configPath)) {
            return $this->critical('configuration.php not found!');
        }

        // Get file permissions as integer
        $perms = fileperms($configPath);

        // Extract last 3 digits of octal representation (e.g., "644" from "100644")
        $octalPerms = substr(sprintf('%o', $perms), -3);

        // Check permission bits using bitwise AND
        // 0x0004 = octal 04 = world-read permission (last digit >= 4)
        // 0x0002 = octal 02 = world-write permission (last digit is 2, 3, 6, or 7)
        $worldReadable = ($perms & 0x0004);
        $worldWritable = ($perms & 0x0002);

        // CRITICAL: World-writable allows any user on the server to modify credentials
        if ($worldWritable !== 0) {
            return $this->critical(
                sprintf('configuration.php is world-writable (%s). This is a critical security risk.', $octalPerms),
            );
        }

        // WARNING: World-readable allows other users to read credentials (e.g., 644, 664)
        if ($worldReadable !== 0) {
            return $this->warning(
                sprintf(
                    'configuration.php is world-readable (%s). Consider restricting permissions to 640 or 600.',
                    $octalPerms,
                ),
            );
        }

        // GOOD: Restrictive permissions like 600 (owner only) or 640 (owner + group)
        return $this->good(
            sprintf('configuration.php permissions (%s) are appropriately restrictive.', $octalPerms),
        );
    }
}

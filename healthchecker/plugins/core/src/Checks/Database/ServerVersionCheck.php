<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Server Version Health Check
 *
 * This check verifies that the MySQL or MariaDB server version meets Joomla's
 * minimum requirements (MySQL 8.0.13+ or MariaDB 10.4.0+).
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Joomla relies on specific database features and SQL syntax that may not be
 * available in older database versions. Running on an outdated version can cause:
 * - Compatibility issues with Joomla core and extensions
 * - Missing security patches and vulnerability exposure
 * - Lack of performance optimizations available in newer versions
 * - Potential data corruption or query failures
 *
 * RESULT MEANINGS:
 *
 * GOOD: The database server version meets or exceeds the minimum requirements.
 * Your site can use all Joomla features without compatibility concerns.
 *
 * WARNING: The database version is below recommended levels. While the site may
 * function, you should plan an upgrade to avoid future compatibility issues and
 * security vulnerabilities.
 *
 * CRITICAL: The database connection is not available to check the version.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class ServerVersionCheck extends AbstractHealthCheck
{
    /**
     * Minimum supported MySQL version for Joomla 5.
     *
     * @var string
     */
    private const MINIMUM_MYSQL_VERSION = '8.0.13';

    /**
     * Minimum supported MariaDB version for Joomla 5.
     *
     * @var string
     */
    private const MINIMUM_MARIADB_VERSION = '10.4.0';

    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'database.server_version'
     */
    public function getSlug(): string
    {
        return 'database.server_version';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'database'
     */
    public function getCategory(): string
    {
        return 'database';
    }

    /**
     * Perform the database server version health check.
     *
     * Verifies that the MySQL or MariaDB server version meets Joomla's
     * minimum requirements (MySQL 8.0.13+ or MariaDB 10.4.0+).
     *
     * Check logic:
     * 1. Get database version string from server
     * 2. Detect if server is MariaDB (version string contains 'mariadb')
     * 3. Extract numeric version using regex (X.Y.Z format)
     * 4. Compare against minimum version for that server type
     * 5. If below minimum: WARNING - upgrade recommended
     * 6. If meets minimum: GOOD - compatible version
     *
     * @return HealthCheckResult The result with appropriate status and message
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Get the full version string (may include vendor info, build details, etc.)
        $version = $database->getVersion();
        $isMariaDb = stripos($version, 'mariadb') !== false;

        if ($isMariaDb) {
            // Extract numeric version from MariaDB version string (format: X.Y.Z)
            preg_match('/(\d+\.\d+\.\d+)/', $version, $matches);
            $numericVersion = $matches[1] ?? '0.0.0';

            // Compare against MariaDB minimum version requirement
            if (version_compare($numericVersion, self::MINIMUM_MARIADB_VERSION, '<')) {
                return $this->warning(
                    sprintf(
                        'MariaDB %s is below recommended version %s.',
                        $numericVersion,
                        self::MINIMUM_MARIADB_VERSION,
                    ),
                );
            }

            return $this->good(sprintf('MariaDB %s meets requirements.', $numericVersion));
        }

        // Extract numeric version from MySQL version string (format: X.Y.Z)
        preg_match('/(\d+\.\d+\.\d+)/', $version, $matches);
        $numericVersion = $matches[1] ?? '0.0.0';

        // Compare against MySQL minimum version requirement
        if (version_compare($numericVersion, self::MINIMUM_MYSQL_VERSION, '<')) {
            return $this->warning(
                sprintf('MySQL %s is below recommended version %s.', $numericVersion, self::MINIMUM_MYSQL_VERSION),
            );
        }

        return $this->good(sprintf('MySQL %s meets requirements.', $numericVersion));
    }
}

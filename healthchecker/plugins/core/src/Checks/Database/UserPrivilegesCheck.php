<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database User Privileges Health Check
 *
 * This check verifies that the database user has all privileges required
 * for Joomla operations: SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Joomla requires specific database privileges to function properly:
 * - SELECT/INSERT/UPDATE/DELETE: Basic content operations
 * - CREATE/DROP: Extension installation and uninstallation
 * - ALTER: Database migrations and updates
 * - INDEX: Performance optimization during schema changes
 * Missing privileges can cause extension installations to fail or break updates.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The database user has ALL PRIVILEGES or all required individual
 * privileges. Joomla can perform all necessary database operations.
 *
 * WARNING: One or more required privileges appear to be missing. While
 * basic operations may work, extension installation, updates, or certain
 * features may fail. Grant the missing privileges to the database user.
 *
 * CRITICAL: The database connection is not available to check privileges.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class UserPrivilegesCheck extends AbstractHealthCheck
{
    /**
     * List of database privileges required for full Joomla functionality.
     *
     * These privileges are needed for:
     * - SELECT/INSERT/UPDATE/DELETE: Basic content operations
     * - CREATE/DROP: Extension installation and uninstallation
     * - ALTER: Database migrations and updates
     * - INDEX: Performance optimization during schema changes
     *
     * @var array<string>
     */
    private const REQUIRED_PRIVILEGES = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'CREATE', 'DROP', 'ALTER', 'INDEX'];

    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format "database.user_privileges"
     */
    public function getSlug(): string
    {
        return 'database.user_privileges';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug "database"
     */
    public function getCategory(): string
    {
        return 'database';
    }

    /**
     * Perform the database user privileges health check.
     *
     * Verifies that the database user has all privileges required for Joomla operations.
     * This includes basic CRUD operations as well as schema modification privileges
     * needed for installing and updating extensions.
     *
     * The check:
     * 1. Runs SHOW GRANTS FOR CURRENT_USER() to get all granted privileges
     * 2. Checks if "ALL PRIVILEGES" is granted (shortcut - everything is allowed)
     * 3. If not, searches each grant statement for each required privilege
     * 4. Reports any missing privileges that may cause operation failures
     *
     * Note: This check uses string matching on GRANT statements, which may produce
     * false warnings if privileges are granted in non-standard formats. "ALL PRIVILEGES"
     * is definitively detected as complete access.
     *
     * @return HealthCheckResult Critical if database unavailable, warning if privileges
     *                           appear to be missing, good if all required privileges found
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        try {
            // SHOW GRANTS returns statements like:
            // "GRANT ALL PRIVILEGES ON *.* TO 'user'@'host'"
            // "GRANT SELECT, INSERT, UPDATE ON db.* TO 'user'@'host'"
            $query = 'SHOW GRANTS FOR CURRENT_USER()';
            $grants = $database->setQuery($query)
                ->loadColumn();

            $hasAllPrivileges = false;
            $foundPrivileges = [];

            foreach ($grants as $grant) {
                // If user has ALL PRIVILEGES, no need to check individual ones
                if (stripos((string) $grant, 'ALL PRIVILEGES') !== false) {
                    $hasAllPrivileges = true;
                    break;
                }

                // Check each grant statement for each required privilege
                foreach (self::REQUIRED_PRIVILEGES as $priv) {
                    if (stripos((string) $grant, $priv) !== false) {
                        $foundPrivileges[$priv] = true;
                    }
                }
            }

            if ($hasAllPrivileges) {
                return $this->good('Database user has all required privileges.');
            }

            // Find privileges that were not found in any GRANT statement
            $missing = array_diff(self::REQUIRED_PRIVILEGES, array_keys($foundPrivileges));

            if ($missing !== []) {
                return $this->warning(
                    sprintf('Database user may be missing privileges: %s', implode(', ', $missing)),
                );
            }

            return $this->good('Database user has all required privileges.');
        } catch (\Exception $exception) {
            return $this->warning('Unable to check database privileges: ' . $exception->getMessage());
        }
    }
}

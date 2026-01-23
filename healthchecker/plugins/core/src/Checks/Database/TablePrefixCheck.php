<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Database Table Prefix Health Check
 *
 * This check examines the Joomla database table prefix configuration to
 * ensure it provides adequate security and avoids potential conflicts.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * The database table prefix provides several benefits:
 * - Security through obscurity: Makes SQL injection attacks harder
 * - Multi-site support: Allows multiple Joomla installations in one database
 * - Conflict prevention: Avoids table name collisions with other applications
 * Using the default "jos_" prefix or no prefix reduces these protections.
 *
 * RESULT MEANINGS:
 *
 * GOOD: A unique table prefix of 3+ characters is configured. Your database
 * tables are distinguishable and have basic obscurity protection.
 *
 * WARNING (no prefix): No table prefix is set. This could cause conflicts
 * if other applications share the database, and provides no obscurity.
 *
 * WARNING (default prefix): The default "jos_" prefix is in use. Automated
 * attack scripts often target this prefix. Consider changing it.
 *
 * WARNING (short prefix): The table prefix is less than 3 characters.
 * Consider using a longer, more unique prefix.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class TablePrefixCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format "database.table_prefix"
     */
    public function getSlug(): string
    {
        return 'database.table_prefix';
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
     * Perform the table prefix health check.
     *
     * Examines the Joomla database table prefix configuration to ensure it provides
     * adequate security and avoids potential conflicts. The table prefix is important for:
     * - Security through obscurity (makes SQL injection harder)
     * - Multi-site support (multiple Joomla installations in one database)
     * - Conflict prevention (avoids table name collisions with other applications)
     *
     * The check validates:
     * - Prefix is not empty (prevents table name conflicts)
     * - Prefix is not the default "jos_" (reduces automated attack surface)
     * - Prefix is at least 3 characters long (better uniqueness)
     *
     * @return HealthCheckResult Warning if prefix is empty, default "jos_", or too short;
     *                           good if a unique prefix of 3+ characters is configured
     */
    protected function performCheck(): HealthCheckResult
    {
        $prefix = Factory::getApplication()->get('dbprefix');

        // No prefix at all could cause conflicts with other applications
        if (empty($prefix)) {
            return $this->warning('No database table prefix is set. This could cause conflicts.');
        }

        // The default "jos_" prefix is well-known and targeted by automated attacks
        if ($prefix === 'jos_') {
            return $this->warning(
                'Using default table prefix "jos_". Consider using a unique prefix for better security.',
            );
        }

        // Very short prefixes provide minimal obscurity benefit
        if (strlen((string) $prefix) < 3) {
            return $this->warning(
                sprintf('Table prefix "%s" is very short. Consider using a longer prefix.', $prefix),
            );
        }

        return $this->good(sprintf('Table prefix "%s" is configured.', $prefix));
    }
}

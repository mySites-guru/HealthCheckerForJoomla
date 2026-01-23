<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * PDO MySQL Extension Health Check
 *
 * This check verifies that the PHP PDO MySQL driver is loaded for database connectivity.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * PDO MySQL is the primary database driver for Joomla on MySQL/MariaDB:
 * - All database queries go through PDO for security (prepared statements)
 * - User authentication and session management
 * - Content storage and retrieval (articles, categories, menus)
 * - Extension data and configuration storage
 * - Core system tables and cache management
 * Without PDO MySQL, Joomla cannot connect to or interact with the database.
 *
 * RESULT MEANINGS:
 *
 * GOOD: PDO MySQL extension is loaded. Database connectivity is available.
 *
 * CRITICAL: PDO MySQL extension is not available. Joomla cannot connect to
 *           the MySQL/MariaDB database. Contact your hosting provider to
 *           enable the pdo_mysql extension.
 *
 * Note: This check does not produce WARNING results as PDO MySQL is a hard requirement.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class PdoMysqlExtensionCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.pdo_mysql_extension'
     */
    public function getSlug(): string
    {
        return 'system.pdo_mysql_extension';
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
     * Verify that the PDO MySQL extension is loaded.
     *
     * Checks if the pdo_mysql PHP extension is available. This extension is absolutely
     * required for Joomla to connect to MySQL/MariaDB databases using PDO, which is
     * the only supported database driver for Joomla.
     *
     * @return HealthCheckResult CRITICAL if pdo_mysql is not loaded, GOOD if available
     */
    /**
     * Perform the Pdo Mysql Extension health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        if (! extension_loaded('pdo_mysql')) {
            return $this->critical(
                'PDO MySQL extension is not loaded. This is required for Joomla database connectivity.',
            );
        }

        return $this->good('PDO MySQL extension is loaded.');
    }
}

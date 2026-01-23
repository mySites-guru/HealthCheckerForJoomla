<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * PHP SAPI Health Check
 *
 * This check identifies the Server API (SAPI) that PHP is running under. The SAPI
 * determines how PHP interfaces with the web server and affects performance, process
 * management, and available features.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Different SAPIs have significant performance implications. PHP-FPM (fpm-fcgi) offers
 * better process management, memory efficiency, and performance compared to mod_php
 * (apache2handler). LiteSpeed and FrankenPHP are also high-performance options. Understanding
 * your SAPI helps optimize server configuration for Joomla.
 *
 * RESULT MEANINGS:
 *
 * GOOD: For fpm-fcgi, cgi-fcgi, litespeed, or frankenphp - these are recommended for
 * production due to better performance and resource management. For apache2handler,
 * the site works but PHP-FPM would provide better performance. Other SAPIs are reported
 * informatively.
 *
 * WARNING: Running via CLI indicates the check is being executed from command line rather
 * than through a web server, which may not reflect production configuration.
 *
 * CRITICAL: This check does not produce critical results.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class PhpSapiCheck extends AbstractHealthCheck
{
    /**
     * List of recommended SAPI types for production environments.
     *
     * These SAPIs provide better process management, memory efficiency,
     * and performance compared to older options like mod_php.
     */
    private const RECOMMENDED_SAPIS = ['fpm-fcgi', 'cgi-fcgi', 'litespeed', 'frankenphp'];

    /**
     * Get the unique identifier for this health check.
     *
     * @return string The check slug in format 'system.php_sapi'
     */
    public function getSlug(): string
    {
        return 'system.php_sapi';
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
     * Identify and report the PHP Server API (SAPI) in use.
     *
     * The SAPI determines how PHP interfaces with the web server and has significant
     * impact on performance and resource management. Modern SAPIs like PHP-FPM offer
     * better process management and performance than older options like mod_php.
     * This check reports the current SAPI and provides guidance on optimal configurations.
     *
     * @return HealthCheckResult WARNING if running via CLI (non-web context),
     *                           GOOD for all web SAPIs with performance notes
     */
    protected function performCheck(): HealthCheckResult
    {
        $sapi = PHP_SAPI;

        // CLI context - this check is meant for web environments
        if ($sapi === 'cli') {
            return $this->warning('Running via CLI. This check is meant for web environments.');
        }

        // Recommended modern SAPIs for production
        if (in_array($sapi, self::RECOMMENDED_SAPIS, true)) {
            return $this->good(sprintf('PHP SAPI: %s (recommended for performance).', $sapi));
        }

        // Apache mod_php - works but FPM would be better
        if ($sapi === 'apache2handler') {
            return $this->good(sprintf('PHP SAPI: %s (consider PHP-FPM for better performance).', $sapi));
        }

        // Other SAPI - report informatively
        return $this->good(sprintf('PHP SAPI: %s', $sapi));
    }
}

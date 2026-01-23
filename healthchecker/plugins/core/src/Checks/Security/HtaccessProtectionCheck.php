<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * .htaccess Protection Health Check
 *
 * This check verifies that the .htaccess file exists and is properly configured
 * for Apache web servers. The .htaccess file provides URL rewriting, access controls,
 * and security headers.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Joomla ships with a htaccess.txt file that must be renamed to .htaccess for Apache
 * protection. This file blocks direct access to sensitive files, enables SEF URLs,
 * and can prevent many common attack vectors. Without it, your site may be vulnerable
 * and SEO-friendly URLs will not work.
 *
 * RESULT MEANINGS:
 *
 * GOOD: The .htaccess file exists and has RewriteEngine configured, indicating it
 *       is properly set up for Joomla's security and URL rewriting features.
 *
 * WARNING: Either the .htaccess file is missing (rename htaccess.txt to .htaccess),
 *          the file is empty, or RewriteEngine is not enabled. Your site may lack
 *          important protections.
 *
 * CRITICAL: Not applicable for this check.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class HtaccessProtectionCheck extends AbstractHealthCheck
{
    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'security.htaccess_protection'
     */
    public function getSlug(): string
    {
        return 'security.htaccess_protection';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category identifier 'security'
     */
    public function getCategory(): string
    {
        return 'security';
    }

    /**
     * Perform the .htaccess file protection check.
     *
     * Verifies that the .htaccess file exists and is properly configured for Apache
     * web servers. This file provides URL rewriting for SEF URLs, access controls to
     * protect sensitive directories, and security headers. Joomla ships with htaccess.txt
     * that must be renamed to .htaccess to take effect.
     *
     * @return HealthCheckResult WARNING if .htaccess is missing, empty, or missing RewriteEngine,
     *                          GOOD if .htaccess exists and appears properly configured
     */
    protected function performCheck(): HealthCheckResult
    {
        // .htaccess must be in the site root directory
        $htaccessPath = JPATH_ROOT . '/.htaccess';

        // File doesn't exist - needs to be created from htaccess.txt
        if (! file_exists($htaccessPath)) {
            return $this->warning(
                '.htaccess file not found. Consider renaming htaccess.txt to .htaccess for Apache protection.',
            );
        }

        // Read .htaccess content to check configuration
        $htaccessContent = file_get_contents($htaccessPath);

        // Empty file or read error - no protection rules in place
        if (in_array($htaccessContent, ['', '0', false], true)) {
            return $this->warning('.htaccess file is empty.');
        }

        // Check for basic security rules - RewriteEngine is core to Joomla's .htaccess
        $hasRewriteEngine = stripos($htaccessContent, 'RewriteEngine') !== false;

        // Missing RewriteEngine - SEF URLs and many protections won't work
        if (! $hasRewriteEngine) {
            return $this->warning('.htaccess exists but may not have URL rewriting enabled.');
        }

        // File exists with RewriteEngine enabled - basic protection in place
        return $this->good('.htaccess file is present and configured.');
    }
}

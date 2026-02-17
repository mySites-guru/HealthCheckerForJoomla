<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Index File Health Check
 *
 * This check detects placeholder index files (index.html, index.htm, default.html, etc.)
 * in the Joomla site root that could be served instead of index.php.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Hosting providers often place default placeholder files like index.html in the web root.
 * These files can be served by direct request and potentially indexed by search engines,
 * exposing hosting provider branding or blank pages instead of your Joomla site content.
 *
 * RESULT MEANINGS:
 *
 * GOOD: No placeholder index files found in the site root. Only Joomla's index.php
 *       will be served as the entry point.
 *
 * WARNING: One or more placeholder index files were found. These should be removed
 *          to prevent them being served or indexed by search engines.
 *
 * CRITICAL: Not applicable for this check.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class IndexFileCheck extends AbstractHealthCheck
{
    private const INDEX_FILES = [
        'index.html',
        'index.htm',
        'default.html',
        'Default.html',
        'default.htm',
        'Default.htm',
    ];

    /**
     * Get the unique identifier for this check.
     *
     * @return string The check slug in format 'security.index_file'
     */
    public function getSlug(): string
    {
        return 'security.index_file';
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

    public function getDocsUrl(): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Security/IndexFileCheck.php';
    }

    /**
     * Perform the index file check.
     *
     * Checks for the presence of placeholder index files in the site root directory
     * that could be served instead of Joomla's index.php entry point.
     *
     * @return HealthCheckResult WARNING if any placeholder index files are found,
     *                          GOOD if no placeholder index files exist
     */
    protected function performCheck(): HealthCheckResult
    {
        $foundFiles = [];

        foreach (self::INDEX_FILES as $filename) {
            if (file_exists(JPATH_ROOT . '/' . $filename)) {
                $foundFiles[] = $filename;
            }
        }

        if ($foundFiles !== []) {
            return $this->warning(
                'Placeholder index file(s) found: ' . implode(
                    ', ',
                    $foundFiles,
                ) . '. These files should be removed to prevent them being served or indexed by search engines.',
            );
        }

        return $this->good('No placeholder index files found in the site root.');
    }
}

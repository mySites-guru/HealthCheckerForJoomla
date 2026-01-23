<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Template Health Check
 *
 * This check validates the integrity of the active site and administrator templates
 * by verifying that required files exist and are properly formed.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Templates are essential for rendering your Joomla site. If template files are
 * missing, corrupted, or misconfigured, your site may display incorrectly or fail
 * to load entirely. This check verifies the presence of templateDetails.xml
 * (the manifest) and index.php (the main template file) for both templates.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Both site and administrator templates are properly configured with
 * valid manifest files and required template files present.
 *
 * WARNING: This check does not return WARNING status.
 *
 * CRITICAL: Template issues detected that may prevent your site from displaying
 * correctly. Issues include: missing template directories, missing or invalid
 * templateDetails.xml files, or missing index.php files.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class TemplateCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in the format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'extensions.template';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug
     */
    public function getCategory(): string
    {
        return 'extensions';
    }

    /**
     * Perform the template integrity check.
     *
     * Validates that both the active site and administrator templates are properly
     * configured and have all required files. Template integrity is critical because
     * missing or corrupted template files will prevent the site from displaying.
     *
     * Template requirements:
     * - Template directory must exist
     * - templateDetails.xml manifest file must exist and be valid XML
     * - index.php file must exist (main template entry point)
     *
     * Checks both:
     * - Site template (client_id = 0, home = 1): Frontend display
     * - Admin template (client_id = 1, home = 1): Backend display
     *
     * @return HealthCheckResult The result with status and description
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Get active site template (client_id = 0 for frontend, home = 1 for default)
        $query = $database->getQuery(true)
            ->select(['template', 'title'])
            ->from($database->quoteName('#__template_styles'))
            ->where($database->quoteName('client_id') . ' = 0')
            ->where($database->quoteName('home') . ' = 1');

        $siteTemplate = $database->setQuery($query)
            ->loadObject();

        // Get active admin template (client_id = 1 for backend, home = 1 for default)
        $query = $database->getQuery(true)
            ->select(['template', 'title'])
            ->from($database->quoteName('#__template_styles'))
            ->where($database->quoteName('client_id') . ' = 1')
            ->where($database->quoteName('home') . ' = 1');

        $adminTemplate = $database->setQuery($query)
            ->loadObject();

        $issues = [];

        // Validate site template files and structure
        if ($siteTemplate !== null) {
            $siteTemplatePath = JPATH_SITE . '/templates/' . $siteTemplate->template;
            $siteIssues = $this->validateTemplate($siteTemplatePath, $siteTemplate->template, 'Site');

            if ($siteIssues !== []) {
                $issues = array_merge($issues, $siteIssues);
            }
        } else {
            $issues[] = 'No default site template configured';
        }

        // Validate admin template files and structure
        if ($adminTemplate !== null) {
            $adminTemplatePath = JPATH_ADMINISTRATOR . '/templates/' . $adminTemplate->template;
            $adminIssues = $this->validateTemplate($adminTemplatePath, $adminTemplate->template, 'Admin');

            if ($adminIssues !== []) {
                $issues = array_merge($issues, $adminIssues);
            }
        } else {
            $issues[] = 'No default admin template configured';
        }

        // Any template issues are critical - site may not display
        if ($issues !== []) {
            return $this->critical(sprintf('Template issues: %s', implode('; ', $issues)));
        }

        return $this->good(
            sprintf(
                'Site template "%s" and admin template "%s" are valid.',
                $siteTemplate->template ?? 'unknown',
                $adminTemplate->template ?? 'unknown',
            ),
        );
    }

    /**
     * Validate a template's required files and structure.
     *
     * Checks for the presence and validity of critical template files:
     * - Template directory must exist
     * - templateDetails.xml must exist and be valid XML
     * - index.php must exist (main template file)
     *
     * @param string $path The absolute path to the template directory
     * @param string $name The template name (directory name)
     * @param string $type The template type ('Site' or 'Admin') for error messages
     *
     * @return array List of issues found, empty array if template is valid
     */
    private function validateTemplate(string $path, string $name, string $type): array
    {
        $issues = [];

        // Template directory must exist
        if (! is_dir($path)) {
            $issues[] = sprintf('%s template "%s" directory not found', $type, $name);

            // If directory doesn't exist, can't check files - return early
            return $issues;
        }

        // Check for templateDetails.xml (required manifest file)
        $xmlPath = $path . '/templateDetails.xml';

        if (! file_exists($xmlPath)) {
            $issues[] = sprintf('%s template "%s" missing templateDetails.xml', $type, $name);
        } else {
            // Verify XML is valid and can be parsed
            $xml = @simplexml_load_file($xmlPath);

            if (! $xml) {
                $issues[] = sprintf('%s template "%s" has invalid templateDetails.xml', $type, $name);
            }
        }

        // Check for index.php (required for rendering - main template entry point)
        $indexPath = $path . '/index.php';

        if (! file_exists($indexPath)) {
            $issues[] = sprintf('%s template "%s" missing index.php', $type, $name);
        }

        return $issues;
    }
}

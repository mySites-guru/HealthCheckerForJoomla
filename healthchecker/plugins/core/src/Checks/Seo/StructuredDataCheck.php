<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Schema.org Structured Data Health Check
 *
 * This check detects whether a structured data or Schema.org plugin is installed,
 * which enables rich snippets and enhanced search result appearances.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Schema.org structured data (often implemented as JSON-LD) helps search engines
 * understand your content beyond just text. It enables rich snippets like star
 * ratings, recipe cards, event details, FAQ accordions, and breadcrumbs directly
 * in search results. These enhanced listings stand out and typically receive
 * significantly higher click-through rates than plain results.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Either a structured data plugin is detected and enabled, or no plugin
 * was found but this is informational only. Structured data is beneficial but
 * not strictly required for basic SEO.
 *
 * WARNING: This check does not currently return warnings. The absence of
 * structured data is noted as informational since it's an enhancement rather
 * than a requirement.
 *
 * CRITICAL: This check does not return critical status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class StructuredDataCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'seo.structured_data'
     */
    public function getSlug(): string
    {
        return 'seo.structured_data';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'seo'
     */
    public function getCategory(): string
    {
        return 'seo';
    }

    /**
     * Perform the Schema.org structured data plugin detection check.
     *
     * Searches for enabled plugins that implement Schema.org structured data markup,
     * typically using JSON-LD format. Structured data helps search engines understand
     * page content semantically, enabling rich snippets and enhanced search results.
     *
     * The check searches for plugins containing keywords related to:
     * - Schema.org markup ('schema' in name or element)
     * - Structured data ('structured data' in name)
     * - JSON-LD format ('json-ld' or 'jsonld' in name or element)
     * - Rich snippets ('rich snippet' in name)
     *
     * Structured data enables features like:
     * - Star ratings and review counts
     * - Recipe cards with cooking time and calories
     * - Event details (date, location, price)
     * - FAQ accordions directly in search results
     * - Breadcrumb navigation
     * - Article metadata (author, publish date)
     * - Product information (price, availability)
     *
     * While not strictly required for basic SEO, structured data significantly
     * enhances visibility and click-through rates in search results, so this check
     * returns informational "good" status regardless of findings.
     *
     * @return HealthCheckResult Always Good with informational message about plugin status
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Search for enabled plugins that implement Schema.org structured data
        // Checks common keywords in both plugin name and element fields
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('plugin'))
            ->where($database->quoteName('enabled') . ' = 1')
            ->where(
                '(' .
                // Search plugin name for Schema.org and structured data keywords
                $database->quoteName('name') . ' LIKE ' . $database->quote('%schema%') .
                ' OR ' . $database->quoteName('name') . ' LIKE ' . $database->quote('%structured%data%') .
                ' OR ' . $database->quoteName('name') . ' LIKE ' . $database->quote('%json-ld%') .
                ' OR ' . $database->quoteName('name') . ' LIKE ' . $database->quote('%jsonld%') .
                ' OR ' . $database->quoteName('name') . ' LIKE ' . $database->quote('%rich%snippet%') .
                // Search plugin element for Schema.org and JSON-LD indicators
                ' OR ' . $database->quoteName('element') . ' LIKE ' . $database->quote('%schema%') .
                ' OR ' . $database->quoteName('element') . ' LIKE ' . $database->quote('%jsonld%') .
                ')',
            );

        $database->setQuery($query);
        $schemaPluginCount = (int) $database->loadResult();

        // If structured data plugins are detected, rich snippets are likely configured
        if ($schemaPluginCount > 0) {
            return $this->good(
                sprintf('Found %d enabled plugin(s) that may provide Schema.org structured data.', $schemaPluginCount),
            );
        }

        // No plugins found, but return "good" since structured data is an enhancement
        // rather than a strict requirement - it's beneficial but not critical
        return $this->good(
            'No structured data plugin detected. Consider adding Schema.org markup to enhance search result appearances with rich snippets.',
        );
    }
}

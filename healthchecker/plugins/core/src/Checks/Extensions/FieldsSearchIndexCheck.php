<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Custom Fields Search Index Health Check
 *
 * This check examines whether published custom fields have Smart Search
 * indexing enabled. When custom fields exist but none are configured for
 * Smart Search indexing, site visitors may get incomplete or irrelevant
 * search results because field values are excluded from the search index.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Custom fields often contain important content such as product details,
 * event dates, or location information. If Smart Search indexing is
 * disabled for all custom fields, this content is invisible to the
 * search engine. This is common on YOOtheme sites where custom fields
 * are heavily used for layout content. Users frequently forget to
 * enable the Search Index parameter in field options.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Either no published custom fields exist, Smart Search is
 * disabled, or at least one custom field has search indexing enabled.
 *
 * WARNING: Published custom fields exist and Smart Search is enabled,
 * but none of the fields have search indexing configured. Navigate to
 * Content > Fields and edit each field's Options tab to set the
 * Search Index parameter.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use Joomla\CMS\Language\Text;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class FieldsSearchIndexCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check identifier in format 'category.check_name'
     */
    public function getSlug(): string
    {
        return 'extensions.fields_search_index';
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

    public function getDocsUrl(?HealthStatus $healthStatus = null): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Extensions/FieldsSearchIndexCheck.php';
    }

    public function getActionUrl(?HealthStatus $healthStatus = null): ?string
    {
        if ($healthStatus === HealthStatus::Warning) {
            return '/administrator/index.php?option=com_fields&view=fields';
        }

        return null;
    }

    /**
     * Perform the custom fields search index check.
     *
     * Checks whether published custom fields have Smart Search indexing
     * configured. The check only triggers a warning when all three
     * conditions are met: Smart Search is enabled, published custom
     * fields exist, and none of them have search indexing turned on.
     *
     * The search index parameter is stored in the field's params JSON
     * column as the "searchindex" key. A value of "" or "0" means
     * indexing is disabled for that field.
     *
     * @return HealthCheckResult Returns GOOD if no issue found,
     *                           WARNING if fields lack search indexing
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Check if Smart Search component (com_finder) is enabled
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('element') . ' = ' . $database->quote('com_finder'))
            ->where($database->quoteName('enabled') . ' = 1');

        $finderEnabled = (int) $database->setQuery($query)
            ->loadResult() > 0;

        if (! $finderEnabled) {
            return $this->good(Text::_('COM_HEALTHCHECKER_CHECK_EXTENSIONS_FIELDS_SEARCH_INDEX_GOOD'));
        }

        // Count published custom fields across all contexts
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__fields'))
            ->where($database->quoteName('state') . ' = 1');

        $totalFields = (int) $database->setQuery($query)
            ->loadResult();

        if ($totalFields === 0) {
            return $this->good(Text::_('COM_HEALTHCHECKER_CHECK_EXTENSIONS_FIELDS_SEARCH_INDEX_GOOD_2'));
        }

        // Count published fields where searchindex param is set to a value > 0
        // The params column is JSON. Fields with indexing enabled have
        // "searchindex":"1" or "searchindex":"2" (taxonomy mode).
        // Fields without it have "searchindex":"0", "searchindex":"", or
        // the key is missing entirely.
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__fields'))
            ->where($database->quoteName('state') . ' = 1')
            ->where($database->quoteName('params') . ' REGEXP ' . $database->quote('"searchindex":"[1-9]'));

        $indexedFields = (int) $database->setQuery($query)
            ->loadResult();

        if ($indexedFields === 0) {
            return $this->warning(
                Text::sprintf('COM_HEALTHCHECKER_CHECK_EXTENSIONS_FIELDS_SEARCH_INDEX_WARNING', $totalFields),
            );
        }

        return $this->good(
            Text::sprintf(
                'COM_HEALTHCHECKER_CHECK_EXTENSIONS_FIELDS_SEARCH_INDEX_GOOD_3',
                $indexedFields,
                $totalFields,
            ),
        );
    }
}

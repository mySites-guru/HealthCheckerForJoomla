<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Language Packs Health Check
 *
 * This check inventories the installed language packs for both the frontend (site)
 * and backend (administrator) areas of your Joomla installation.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Understanding which languages are installed helps with multilingual site management
 * and cleanup. Unused language packs consume disk space and may require updates.
 * This is an informational check to help you understand your site's language configuration.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Reports the count of installed site and administrator language packs.
 * This is informational only - any number of language packs is acceptable.
 *
 * WARNING: This check does not return WARNING status.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class LanguagePacksCheck extends AbstractHealthCheck
{
    /**
     * Returns the unique slug identifier for this health check.
     *
     * @return string The check slug in format 'extensions.language_packs'
     */
    public function getSlug(): string
    {
        return 'extensions.language_packs';
    }

    /**
     * Returns the category this check belongs to.
     *
     * @return string The category slug 'extensions'
     */
    public function getCategory(): string
    {
        return 'extensions';
    }

    /**
     * Performs the language packs health check.
     *
     * This method inventories installed language packs by querying the #__extensions
     * table for extensions of type 'language'. Languages are separated by client_id:
     * - client_id = 0: Site/Frontend languages
     * - client_id = 1: Administrator/Backend languages
     *
     * This is an informational check - any number of language packs is acceptable.
     * It helps administrators understand their multilingual site configuration and
     * identify unused language packs that could be removed to save disk space and
     * reduce update overhead.
     *
     * Note: Each language pack includes translations for core Joomla strings plus
     * any extension language overrides in that language.
     *
     * @return HealthCheckResult Always returns GOOD with count of site and admin languages
     */
    /**
     * Perform the Language Packs health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        // Count frontend/site languages (client_id = 0)
        // These are displayed to site visitors
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('language'))
            ->where($database->quoteName('client_id') . ' = 0');

        $siteLanguages = (int) $database->setQuery($query)
            ->loadResult();

        // Count backend/administrator languages (client_id = 1)
        // These are used in the Joomla administrator interface
        $query = $database->getQuery(true)
            ->select('COUNT(*)')
            ->from($database->quoteName('#__extensions'))
            ->where($database->quoteName('type') . ' = ' . $database->quote('language'))
            ->where($database->quoteName('client_id') . ' = 1');

        $adminLanguages = (int) $database->setQuery($query)
            ->loadResult();

        // This is informational only - any count is acceptable
        return $this->good(
            sprintf('%d site language(s), %d admin language(s) installed.', $siteLanguages, $adminLanguages),
        );
    }
}

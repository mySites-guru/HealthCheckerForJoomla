<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Legacy Extensions Health Check
 *
 * This check identifies enabled third-party extensions that have not been
 * updated in over 2 years, based on the creation date in their manifest files.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Extensions that have not been updated for extended periods may be abandoned
 * by their developers. Abandoned extensions will not receive security patches,
 * may become incompatible with newer Joomla versions, and could contain outdated
 * code practices. Consider finding actively maintained alternatives.
 *
 * NOTE: Joomla core extensions are automatically excluded from this check using
 * a hardcoded list of known core extension element names.
 *
 * RESULT MEANINGS:
 *
 * GOOD: All enabled third-party extensions have been updated within the last 2 years.
 *
 * WARNING: One or more enabled extensions have not been updated in over 2 years.
 * Consider whether these extensions are still maintained and if alternatives exist.
 * More than 10 legacy extensions indicates significant technical debt.
 *
 * CRITICAL: This check does not return CRITICAL status.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions;

use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;

\defined('_JEXEC') || die;

final class LegacyExtensionsCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'extensions.legacy_extensions'
     */
    public function getSlug(): string
    {
        return 'extensions.legacy_extensions';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'extensions'
     */
    public function getCategory(): string
    {
        return 'extensions';
    }

    /**
     * Perform the Legacy Extensions health check.
     *
     * @return HealthCheckResult The result of this health check
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();

        // Calculate date threshold (extensions not updated in over 2 years)
        $threshold = (new \DateTime())->modify('-2 years')
            ->format('Y-m-d');

        // Query all enabled extensions (we'll filter core extensions in PHP using manifest data)
        $query = $database
            ->getQuery(true)
            ->select(['name', 'element', 'manifest_cache'])
            ->from($database->quoteName('#__extensions'))
            ->where(
                $database->quoteName('type') .
                    ' IN (' .
                    implode(
                        ',',
                        [$database->quote('component'), $database->quote('module'), $database->quote('plugin')],
                    ) .
                    ')',
            )
            ->where($database->quoteName('enabled') . ' = 1');

        $extensions = $database->setQuery($query)
            ->loadObjectList();
        $legacyExtensions = [];

        foreach ($extensions as $extension) {
            $manifest = json_decode((string) $extension->manifest_cache, true);
            if (! \is_array($manifest)) {
                continue;
            }

            if ($manifest === []) {
                continue;
            }

            // Skip Joomla core extensions
            if ($this->isJoomlaCoreExtension($extension->element)) {
                continue;
            }

            // Get the creation/update date from manifest
            $extensionDate = $this->getExtensionDate($manifest);

            if ($extensionDate === null) {
                continue;
            }

            // Check if the extension is older than threshold
            if ($extensionDate < $threshold) {
                $legacyExtensions[] = $extension->name;
            }
        }

        $legacyCount = \count($legacyExtensions);

        if ($legacyCount > 10) {
            return $this->warning(
                sprintf(
                    '%d third-party extension(s) have not been updated in over 2 years. Consider replacing or removing outdated extensions: %s',
                    $legacyCount,
                    implode(', ', array_slice($legacyExtensions, 0, 20)) . ($legacyCount > 20 ? '...' : ''),
                ),
            );
        }

        if ($legacyCount > 0) {
            return $this->warning(
                sprintf(
                    '%d third-party extension(s) have not been updated in over 2 years: %s',
                    $legacyCount,
                    implode(', ', $legacyExtensions),
                ),
            );
        }

        return $this->good('All enabled third-party extensions have been updated within the last 2 years.');
    }

    /**
     * Check if an extension is a Joomla core extension.
     *
     * Uses a hardcoded list of known Joomla core extension element names.
     *
     * @param string $element The extension element name (e.g., 'com_content', 'plg_system_cache')
     *
     * @return bool True if this is a Joomla core extension
     */
    private function isJoomlaCoreExtension(string $element): bool
    {
        return \in_array($element, $this->getJoomlaCoreExtensions(), true);
    }

    /**
     * Get the list of Joomla core extension element names.
     *
     * This includes all components, modules, and plugins shipped with Joomla 5.x.
     *
     * @return array<string> List of core extension element names
     */
    private function getJoomlaCoreExtensions(): array
    {
        return [
            // Core components
            'com_actionlogs',
            'com_admin',
            'com_ajax',
            'com_associations',
            'com_banners',
            'com_cache',
            'com_categories',
            'com_checkin',
            'com_config',
            'com_contact',
            'com_content',
            'com_contenthistory',
            'com_cpanel',
            'com_fields',
            'com_finder',
            'com_guidedtours',
            'com_installer',
            'com_joomlaupdate',
            'com_languages',
            'com_login',
            'com_mails',
            'com_media',
            'com_menus',
            'com_messages',
            'com_modules',
            'com_newsfeeds',
            'com_plugins',
            'com_postinstall',
            'com_privacy',
            'com_redirect',
            'com_scheduler',
            'com_schemas',
            'com_tags',
            'com_templates',
            'com_users',
            'com_workflow',
            'com_wrapper',

            // Core admin modules
            'mod_custom',
            'mod_feed',
            'mod_frontend',
            'mod_guidedtours',
            'mod_latest',
            'mod_latestactions',
            'mod_logged',
            'mod_login',
            'mod_loginsupport',
            'mod_menu',
            'mod_messages',
            'mod_multilangstatus',
            'mod_popular',
            'mod_post_installation_messages',
            'mod_privacy_dashboard',
            'mod_privacy_status',
            'mod_quickicon',
            'mod_sampledata',
            'mod_stats_admin',
            'mod_submenu',
            'mod_title',
            'mod_toolbar',
            'mod_user',
            'mod_version',

            // Core site modules
            'mod_articles_archive',
            'mod_articles_categories',
            'mod_articles_category',
            'mod_articles_latest',
            'mod_articles_news',
            'mod_articles_popular',
            'mod_banners',
            'mod_breadcrumbs',
            'mod_finder',
            'mod_footer',
            'mod_languages',
            'mod_random_image',
            'mod_related_items',
            'mod_stats',
            'mod_syndicate',
            'mod_tags_popular',
            'mod_tags_similar',
            'mod_users_latest',
            'mod_whosonline',
            'mod_wrapper',

            // Core plugins - actionlog
            'actionlog',

            // Core plugins - api-authentication
            'basic',
            'token',

            // Core plugins - authentication
            'cookie',
            'joomla',
            'ldap',

            // Core plugins - behaviour
            'compat',
            'taggable',
            'versionable',

            // Core plugins - captcha
            'recaptcha',
            'recaptcha_invisible',

            // Core plugins - content
            'confirmconsent',
            'contact',
            'emailcloak',
            'fields',
            'finder',
            'joomla',
            'loadmodule',
            'pagebreak',
            'pagenavigation',
            'vote',

            // Core plugins - editors
            'codemirror',
            'none',
            'tinymce',

            // Core plugins - editors-xtd
            'article',
            'contact',
            'fields',
            'image',
            'menu',
            'module',
            'pagebreak',
            'readmore',

            // Core plugins - extension
            'finder',
            'joomla',
            'namespacemap',

            // Core plugins - fields
            'calendar',
            'checkboxes',
            'color',
            'editor',
            'imagelist',
            'integer',
            'list',
            'media',
            'radio',
            'sql',
            'subform',
            'text',
            'textarea',
            'url',
            'user',
            'usergrouplist',

            // Core plugins - filesystem
            'local',

            // Core plugins - finder
            'categories',
            'contacts',
            'content',
            'newsfeeds',
            'tags',

            // Core plugins - installer
            'folderinstaller',
            'override',
            'packageinstaller',
            'urlinstaller',
            'webinstaller',

            // Core plugins - media-action
            'crop',
            'resize',
            'rotate',

            // Core plugins - multifactorauth
            'email',
            'fixed',
            'totp',
            'webauthn',
            'yubikey',

            // Core plugins - privacy
            'actionlogs',
            'consents',
            'contact',
            'content',
            'message',
            'user',

            // Core plugins - quickicon
            'downloadkey',
            'eos',
            'extensionupdate',
            'joomlaupdate',
            'overridecheck',
            'phpversioncheck',
            'privacycheck',

            // Core plugins - sampledata
            'blog',
            'multilang',

            // Core plugins - schemaorg
            'blogposting',
            'book',
            'event',
            'jobposting',
            'organization',
            'person',
            'recipe',

            // Core plugins - system
            'accessibility',
            'actionlogs',
            'cache',
            'debug',
            'fields',
            'guidedtours',
            'highlight',
            'httpheaders',
            'jooa11y',
            'languagecode',
            'languagefilter',
            'log',
            'logout',
            'privacyconsent',
            'redirect',
            'remember',
            'schedulerunner',
            'schemaorg',
            'sef',
            'sessiongc',
            'shortcut',
            'skipto',
            'stats',
            'tasknotification',
            'updatenotification',
            'webauthn',

            // Core plugins - task
            'checkfiles',
            'deleteactionlogs',
            'demotasks',
            'globalcheckin',
            'requests',
            'rotatelogs',
            'sessiongc',
            'sitestatus',
            'updatenotification',

            // Core plugins - user
            'contactcreator',
            'joomla',
            'profile',
            'terms',
            'token',

            // Core plugins - webservices
            'banners',
            'config',
            'contact',
            'content',
            'installer',
            'languages',
            'media',
            'menus',
            'messages',
            'modules',
            'newsfeeds',
            'plugins',
            'privacy',
            'redirect',
            'tags',
            'templates',
            'users',

            // Core plugins - workflow
            'featuring',
            'notification',
            'publishing',
        ];
    }

    /**
     * Get the extension date from manifest, preferring version date over creation date.
     *
     * @param array<string, mixed> $manifest The decoded manifest_cache data
     *
     * @return string|null Parsed date in Y-m-d format, or null if unparseable
     */
    private function getExtensionDate(array $manifest): ?string
    {
        // Prefer version/update date if available, fall back to creation date
        $dateString = $manifest['creationDate'] ?? null;

        if (! \is_string($dateString) || $dateString === '') {
            return null;
        }

        return $this->parseCreationDate($dateString);
    }

    private function parseCreationDate(string $date): ?string
    {
        // Try common date formats
        $formats = ['Y-m-d', 'F Y', 'M Y', 'd F Y', 'Y', 'F d, Y', 'd-m-Y', 'm/d/Y'];

        foreach ($formats as $format) {
            $parsed = \DateTime::createFromFormat($format, $date);

            if ($parsed !== false) {
                return $parsed->format('Y-m-d');
            }
        }

        // Try strtotime as fallback
        $timestamp = @strtotime($date);

        if ($timestamp !== false) {
            return date('Y-m-d', $timestamp);
        }

        return null;
    }
}

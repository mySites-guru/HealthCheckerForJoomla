<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

/**
 * Orphaned Database Tables Health Check
 *
 * This check identifies database tables with the Joomla prefix that may have
 * been left behind by uninstalled extensions or failed installations.
 *
 * WHY THIS CHECK IS IMPORTANT:
 * Orphaned tables can:
 * - Waste database storage space
 * - Slow down database operations and backups
 * - Cause confusion during maintenance
 * - Potentially contain sensitive data from removed extensions
 * Identifying orphaned tables helps keep the database clean and efficient.
 *
 * RESULT MEANINGS:
 *
 * GOOD: Reports the total count of tables with the configured Joomla prefix.
 * This serves as a baseline for monitoring database table growth.
 *
 * WARNING: Not currently implemented - this is an informational check that
 * reports table counts for awareness.
 *
 * CRITICAL: The database connection is not available to enumerate tables.
 */

namespace MySitesGuru\HealthChecker\Plugin\Core\Checks\Database;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use MySitesGuru\HealthChecker\Component\Administrator\Check\AbstractHealthCheck;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;

\defined('_JEXEC') || die;

final class OrphanedTablesCheck extends AbstractHealthCheck
{
    /**
     * Get the unique slug identifier for this check.
     *
     * @return string The check slug in format 'database.orphaned_tables'
     */
    public function getSlug(): string
    {
        return 'database.orphaned_tables';
    }

    /**
     * Get the category this check belongs to.
     *
     * @return string The category slug 'database'
     */
    public function getCategory(): string
    {
        return 'database';
    }

    public function getDocsUrl(?HealthStatus $healthStatus = null): string
    {
        return 'https://github.com/mySites-guru/HealthCheckerForJoomla/blob/main/healthchecker/plugins/core/src/Checks/Database/OrphanedTablesCheck.php';
    }

    /**
     * Perform the orphaned database tables health check.
     *
     * Identifies tables with the Joomla prefix that may be orphaned from
     * uninstalled extensions or failed installations.
     *
     * Detection strategy (layered approach for accuracy):
     * 1. Core Joomla tables (hardcoded list of all J5 tables)
     * 2. SQL install files (parses actual CREATE TABLE statements)
     * 3. Prefix matching (fallback for extensions without SQL files)
     * 4. Known shared tables (e.g., akeeba_common)
     *
     * Result logic:
     * - GOOD: 0 orphaned tables - clean database
     * - WARNING: 1-10 orphaned tables - list them for review
     * - CRITICAL: >10 orphaned tables - significant cleanup needed
     *
     * @return HealthCheckResult The result with appropriate status and message
     */
    protected function performCheck(): HealthCheckResult
    {
        $database = $this->requireDatabase();
        $prefix = Factory::getApplication()->get('dbprefix');
        $dbName = Factory::getApplication()->get('db');

        // Query all tables in the database with the Joomla prefix
        $query =
            'SELECT TABLE_NAME
                  FROM information_schema.TABLES
                  WHERE TABLE_SCHEMA = ' .
            $database->quote($dbName) .
            '
                  AND TABLE_NAME LIKE ' .
            $database->quote($prefix . '%');

        $allTables = $database->setQuery($query)
            ->loadColumn();

        // Get all currently installed extensions from the Joomla extensions table
        $query = $database
            ->getQuery(true)
            ->select('element, type')
            ->from('#__extensions')
            ->where('state >= 0'); // Only enabled/installed extensions

        $extensions = $database->setQuery($query)
            ->loadObjectList();

        // Build expected table names using multiple detection strategies
        $expectedTables = [];

        // 1. Core Joomla tables (comprehensive hardcoded list)
        $expectedTables = array_merge($expectedTables, $this->getCoreJoomlaTables($prefix));

        // 2. Tables from SQL install files (most accurate for extensions)
        $expectedTables = array_merge($expectedTables, $this->getExtensionTablesFromSql($prefix));

        // 3. Prefix matching fallback (catches extensions without SQL files)
        $expectedTables = array_merge(
            $expectedTables,
            $this->getExtensionTablesByPrefix($extensions, $allTables, $prefix),
        );

        // 4. Known shared tables (e.g., akeeba_common)
        $expectedTables = array_merge($expectedTables, $this->getKnownSharedTables($prefix));

        // Remove duplicates
        $expectedTables = array_unique($expectedTables);

        // Find orphaned tables (present in database but not in expected list)
        $orphanedTables = array_values(array_diff($allTables, $expectedTables));

        // More than 10 orphaned tables indicates significant cleanup needed
        if (count($orphanedTables) > 10) {
            return $this->critical(
                Text::sprintf(
                    'COM_HEALTHCHECKER_CHECK_DATABASE_ORPHANED_TABLES_CRITICAL',
                    count($orphanedTables),
                    implode(', ', $orphanedTables),
                ),
            );
        }

        // 1-10 orphaned tables is acceptable but worth noting
        if ($orphanedTables !== []) {
            return $this->warning(
                Text::sprintf(
                    'COM_HEALTHCHECKER_CHECK_DATABASE_ORPHANED_TABLES_WARNING',
                    count($allTables),
                    count($orphanedTables),
                    implode(', ', $orphanedTables),
                ),
            );
        }

        // No orphaned tables found
        return $this->good(
            Text::sprintf('COM_HEALTHCHECKER_CHECK_DATABASE_ORPHANED_TABLES_GOOD', count($allTables), $prefix),
        );
    }

    /**
     * Get list of core Joomla 5 table names with the specified prefix.
     *
     * Returns an array of all standard Joomla 5.x core tables that should
     * exist in a typical installation. Used as a baseline to identify
     * tables that may belong to extensions.
     *
     * @param string $prefix Table prefix from Joomla configuration (e.g., 'jos_')
     *
     * @return array<string> Array of prefixed core Joomla table names
     */
    private function getCoreJoomlaTables(string $prefix): array
    {
        return [
            $prefix . 'assets',
            $prefix . 'associations',
            $prefix . 'banner_clients',
            $prefix . 'banner_tracks',
            $prefix . 'banners',
            $prefix . 'categories',
            $prefix . 'contact_details',
            $prefix . 'content',
            $prefix . 'content_frontpage',
            $prefix . 'content_rating',
            $prefix . 'content_types',
            $prefix . 'contentitem_tag_map',
            $prefix . 'extensions',
            $prefix . 'fields',
            $prefix . 'fields_categories',
            $prefix . 'fields_groups',
            $prefix . 'fields_values',
            $prefix . 'finder_filters',
            $prefix . 'finder_links',
            $prefix . 'finder_links_terms',
            $prefix . 'finder_logging',
            $prefix . 'finder_taxonomy',
            $prefix . 'finder_taxonomy_map',
            $prefix . 'finder_terms',
            $prefix . 'finder_terms_common',
            $prefix . 'finder_tokens',
            $prefix . 'finder_tokens_aggregate',
            $prefix . 'finder_types',
            $prefix . 'history',
            $prefix . 'languages',
            $prefix . 'mail_templates',
            $prefix . 'menu',
            $prefix . 'menu_types',
            $prefix . 'messages',
            $prefix . 'messages_cfg',
            $prefix . 'modules',
            $prefix . 'modules_menu',
            $prefix . 'newsfeeds',
            $prefix . 'overrider',
            $prefix . 'postinstall_messages',
            $prefix . 'privacy_consents',
            $prefix . 'privacy_requests',
            $prefix . 'redirect_links',
            $prefix . 'scheduler_tasks',
            $prefix . 'schemas',
            $prefix . 'session',
            $prefix . 'tags',
            $prefix . 'template_overrides',
            $prefix . 'template_styles',
            $prefix . 'ucm_base',
            $prefix . 'ucm_content',
            $prefix . 'update_sites',
            $prefix . 'update_sites_extensions',
            $prefix . 'updates',
            $prefix . 'user_keys',
            $prefix . 'user_mfa',
            $prefix . 'user_notes',
            $prefix . 'user_profiles',
            $prefix . 'user_usergroup_map',
            $prefix . 'usergroups',
            $prefix . 'users',
            $prefix . 'viewlevels',
            $prefix . 'webauthn_credentials',
            $prefix . 'workflows',
            $prefix . 'workflow_associations',
            $prefix . 'workflow_stages',
            $prefix . 'workflow_transitions',

            // Action Logs (com_actionlogs)
            $prefix . 'action_log_config',
            $prefix . 'action_logs',
            $prefix . 'action_logs_extensions',
            $prefix . 'action_logs_users',

            // Guided Tours (com_guidedtours)
            $prefix . 'guidedtours',
            $prefix . 'guidedtour_steps',

            // Scheduler logs (scheduler_tasks already exists above)
            $prefix . 'scheduler_logs',

            // Schema.org structured data
            $prefix . 'schemaorg',

            // TUF (The Update Framework) for secure updates
            $prefix . 'tuf_metadata',
        ];
    }

    /**
     * Get extension tables by parsing SQL install files.
     *
     * Scans administrator/components/com_[star]/sql/install.mysql[star].sql files
     * to extract actual table names that extensions create, then does the same
     * for installed libraries.
     *
     * @param string $prefix Table prefix from Joomla configuration
     *
     * @return array<string> Array of prefixed table names from SQL files
     */
    private function getExtensionTablesFromSql(string $prefix): array
    {
        $tables = $this->getLibraryTablesFromSql($prefix);
        $componentPath = JPATH_ADMINISTRATOR . '/components';

        if (! is_dir($componentPath)) {
            return $tables;
        }

        $components = glob($componentPath . '/com_*', GLOB_ONLYDIR);

        if ($components === false) {
            return $tables;
        }

        foreach ($components as $component) {
            // Try XML manifest first to find the actual SQL install file path
            $sqlPathsFromXml = $this->getSqlPathsFromManifest($component);

            // Fall back to common hardcoded locations
            $sqlPaths = array_merge($sqlPathsFromXml, [
                $component . '/sql/install.mysql.sql',
                $component . '/sql/install.mysql.utf8.sql',
                $component . '/sql/mysql/install.sql',
                $component . '/sql/install.sql',
            ]);

            // Deduplicate paths before parsing
            $sqlPaths = array_unique($sqlPaths);

            foreach ($sqlPaths as $sqlPath) {
                if (is_file($sqlPath) && is_readable($sqlPath)) {
                    $parsedTables = $this->parseTablesFromSql($sqlPath, $prefix);
                    $tables = array_merge($tables, $parsedTables);
                }
            }
        }

        return array_unique($tables);
    }

    /**
     * Get SQL install file paths from a component's XML manifest.
     *
     * Parses the component's manifest XML to find the <install><sql><file> elements,
     * which declare the actual SQL files used during installation. This handles
     * extensions that use non-standard SQL file locations (e.g., DJ-Catalog2 uses
     * install/install.com_djcatalog2.sql instead of sql/install.mysql.sql).
     *
     * @param string $componentDir Full path to the component directory
     *
     * @return array<string> Array of absolute paths to SQL install files
     */
    private function getSqlPathsFromManifest(string $componentDir): array
    {
        $xmlFiles = glob($componentDir . '/*.xml');

        if ($xmlFiles === false || $xmlFiles === []) {
            return [];
        }

        foreach ($xmlFiles as $xmlFile) {
            $paths = $this->getSqlPathsFromManifestFile($xmlFile, $componentDir);

            // Only process the first valid manifest
            if ($paths !== null) {
                return $paths;
            }
        }

        return [];
    }

    /**
     * Get SQL install file paths declared by a single extension manifest.
     *
     * Split out from getSqlPathsFromManifest() because a library keeps its
     * manifest in administrator/manifests/libraries while its payload lives in
     * libraries/[name], so the file to parse and the directory to resolve
     * against are not the same place. For components they are.
     *
     * @param string $xmlFile Full path to the manifest XML file
     * @param string $baseDir Directory the declared SQL paths are relative to
     *
     * @return array<string>|null Absolute paths to SQL install files, or null when
     *                            this file is not an extension manifest declaring SQL
     */
    private function getSqlPathsFromManifestFile(string $xmlFile, string $baseDir): ?array
    {
        $xml = @simplexml_load_file($xmlFile);

        if ($xml === false) {
            return null;
        }

        // Only process extension manifest files
        if ($xml->getName() !== 'extension') {
            return null;
        }

        // Extract <install><sql><file> paths — bail if any level is missing
        $sqlNode = $xml->install->sql ?? null;

        if (! $sqlNode instanceof \SimpleXMLElement) {
            return null;
        }

        if (! property_exists($sqlNode, 'file')) {
            return null;
        }

        if ($sqlNode->file === null) {
            return null;
        }

        $paths = [];

        foreach ($sqlNode->file as $file) {
            $sqlRelativePath = (string) $file;

            if ($sqlRelativePath === '') {
                continue;
            }

            $paths[] = $baseDir . '/' . $sqlRelativePath;
        }

        return $paths;
    }

    /**
     * Get library tables by parsing the SQL install files libraries declare.
     *
     * Libraries create tables like any other extension — typically a registry
     * shared by the several extensions that depend on them — but nothing above
     * this looks at them: the component scan only globs com_[star], and
     * getExtensionTablesByPrefix() skips every row whose type is not
     * "component". Their tables were therefore reported as orphaned on every
     * run, with no way for the site owner to make the warning correct.
     *
     * A library's manifest lives in administrator/manifests/libraries/[name].xml
     * while its files live in libraries/[name], so the manifest is parsed from
     * one path and its declared SQL resolved against the other.
     *
     * @param string $prefix Table prefix from Joomla configuration
     *
     * @return array<string> Array of prefixed table names from SQL files
     */
    private function getLibraryTablesFromSql(string $prefix): array
    {
        $tables = [];
        $manifestPath = JPATH_ADMINISTRATOR . '/manifests/libraries';

        if (! is_dir($manifestPath)) {
            return $tables;
        }

        $manifests = glob($manifestPath . '/*.xml');

        if ($manifests === false) {
            return $tables;
        }

        foreach ($manifests as $manifest) {
            $libraryDir = JPATH_SITE . '/libraries/' . basename($manifest, '.xml');

            $sqlPaths = $this->getSqlPathsFromManifestFile($manifest, $libraryDir) ?? [];

            // Fall back to common hardcoded locations, as the component scan does
            $sqlPaths = array_unique(array_merge($sqlPaths, [
                $libraryDir . '/sql/install.mysql.sql',
                $libraryDir . '/sql/install.mysql.utf8.sql',
                $libraryDir . '/sql/mysql/install.sql',
                $libraryDir . '/sql/install.sql',
            ]));

            foreach ($sqlPaths as $sqlPath) {
                if (is_file($sqlPath) && is_readable($sqlPath)) {
                    $tables = array_merge($tables, $this->parseTablesFromSql($sqlPath, $prefix));
                }
            }
        }

        return array_unique($tables);
    }

    /**
     * Parse table names from an SQL install file.
     *
     * Extracts table names from CREATE TABLE statements, handling:
     * - #__ placeholder prefix (replaced with actual prefix)
     * - MySQL backticks and standard quoting
     * - IF NOT EXISTS clauses
     *
     * @param string $sqlPath Path to the SQL file
     * @param string $prefix  Table prefix from Joomla configuration
     *
     * @return array<string> Array of prefixed table names found in the file
     */
    private function parseTablesFromSql(string $sqlPath, string $prefix): array
    {
        $tables = [];

        $content = @file_get_contents($sqlPath);
        if ($content === false) {
            return $tables;
        }

        // Match CREATE TABLE statements with #__ prefix
        // Handles: CREATE TABLE `#__tablename`, CREATE TABLE IF NOT EXISTS #__tablename, etc.
        $pattern = '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"\']?#__([a-z_0-9]+)[`"\']?/i';

        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $tableName) {
                $tables[] = $prefix . $tableName;
            }
        }

        return $tables;
    }

    /**
     * Get extension tables by prefix matching.
     *
     * For extensions without SQL install files, match database tables
     * that start with the extension element name pattern.
     * e.g., com_admintools matches: admintools, admintools_*, etc.
     *
     * @param array<object{element: string, type: string}> $extensions Installed extensions
     * @param array<string>                                $allTables  All database tables
     * @param string                                       $prefix     Table prefix
     *
     * @return array<string> Array of matched table names
     */
    private function getExtensionTablesByPrefix(array $extensions, array $allTables, string $prefix): array
    {
        $matchedTables = [];

        foreach ($extensions as $extension) {
            if ($extension->type !== 'component') {
                continue;
            }

            // Get the element name without com_ prefix
            $element = str_replace('com_', '', $extension->element);
            $element = str_replace(['.', '-'], '_', $element);

            // Match tables that start with this element name
            $tablePrefix = $prefix . $element;

            foreach ($allTables as $allTable) {
                // Match exact table name or table name with underscore suffix
                // e.g., admintools, admintools_badwords, admintools_scans
                if (
                    $allTable === $tablePrefix ||
                    str_starts_with($allTable, $tablePrefix . '_')
                ) {
                    $matchedTables[] = $allTable;
                }
            }
        }

        return array_unique($matchedTables);
    }

    /**
     * Get known shared tables used by multiple extensions.
     *
     * Some tables are shared between related extensions (e.g., akeeba_common
     * is used by both Akeeba Backup and Admin Tools). These should not be
     * flagged as orphaned if only one extension is installed.
     *
     * @param string $prefix Table prefix from Joomla configuration
     *
     * @return array<string> Array of prefixed shared table names
     */
    private function getKnownSharedTables(string $prefix): array
    {
        return [
            // Akeeba shared table (used by both Backup and Admin Tools)
            $prefix . 'akeeba_common',
        ];
    }
}

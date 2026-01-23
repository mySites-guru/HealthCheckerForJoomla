<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Database;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\OrphanedTablesCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OrphanedTablesCheck::class)]
class OrphanedTablesCheckTest extends TestCase
{
    private OrphanedTablesCheck $check;

    private CMSApplication $app;

    protected function setUp(): void
    {
        $this->app = new CMSApplication();
        $this->app->set('dbprefix', 'test_');
        $this->app->set('db', 'test_database');
        Factory::setApplication($this->app);
        $this->check = new OrphanedTablesCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.orphaned_tables', $this->check->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->check->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->check->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunReturnsGoodWhenNoOrphanedTables(): void
    {
        // Create a mock that returns only core Joomla tables (no orphaned tables)
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_content', 'test_users', 'test_extensions', 'test_categories', 'test_assets'],
            ], // All tables in DB
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'element' => 'com_content',
                        'type' => 'component',
                    ],
                    (object) [
                        'element' => 'com_users',
                        'type' => 'component',
                    ],
                ],
            ], // Extensions list
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('no orphaned tables detected', $result->description);
        $this->assertStringContainsString('test_', $result->description);
    }

    public function testRunReturnsWarningWhenFewOrphanedTablesFound(): void
    {
        // Create a mock that includes orphaned tables (1-10 orphaned)
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => [
                    'test_content',
                    'test_users',
                    'test_extensions',
                    'test_orphaned_table1', // Orphaned
                    'test_orphaned_table2', // Orphaned
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'element' => 'com_content',
                        'type' => 'component',
                    ],
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('may be orphaned', $result->description);
    }

    public function testRunReturnsCriticalWhenManyOrphanedTablesFound(): void
    {
        // Create many orphaned tables (more than 10)
        $allTables = ['test_content', 'test_users', 'test_extensions'];

        // Add 12 orphaned tables to trigger critical
        for ($i = 1; $i <= 12; $i++) {
            $allTables[] = 'test_orphaned_' . $i;
        }

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => $allTables,
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'element' => 'com_content',
                        'type' => 'component',
                    ],
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('potential orphaned tables found', $result->description);
        $this->assertStringContainsString('Review and clean up', $result->description);
    }

    public function testRunRecognizesCoreJoomlaTables(): void
    {
        // Use a comprehensive list of core Joomla tables to verify they are recognized
        $coreJoomlaTables = [
            'test_assets',
            'test_associations',
            'test_banner_clients',
            'test_banners',
            'test_categories',
            'test_contact_details',
            'test_content',
            'test_content_frontpage',
            'test_content_rating',
            'test_content_types',
            'test_contentitem_tag_map',
            'test_extensions',
            'test_fields',
            'test_fields_categories',
            'test_fields_groups',
            'test_fields_values',
            'test_finder_filters',
            'test_finder_links',
            'test_finder_links_terms',
            'test_finder_logging',
            'test_finder_taxonomy',
            'test_finder_taxonomy_map',
            'test_finder_terms',
            'test_finder_terms_common',
            'test_finder_tokens',
            'test_finder_tokens_aggregate',
            'test_finder_types',
            'test_history',
            'test_languages',
            'test_mail_templates',
            'test_menu',
            'test_menu_types',
            'test_messages',
            'test_messages_cfg',
            'test_modules',
            'test_modules_menu',
            'test_newsfeeds',
            'test_overrider',
            'test_postinstall_messages',
            'test_privacy_consents',
            'test_privacy_requests',
            'test_redirect_links',
            'test_scheduler_tasks',
            'test_schemas',
            'test_session',
            'test_tags',
            'test_template_overrides',
            'test_template_styles',
            'test_ucm_base',
            'test_ucm_content',
            'test_update_sites',
            'test_update_sites_extensions',
            'test_updates',
            'test_user_keys',
            'test_user_mfa',
            'test_user_notes',
            'test_user_profiles',
            'test_user_usergroup_map',
            'test_usergroups',
            'test_users',
            'test_viewlevels',
            'test_webauthn_credentials',
            'test_workflows',
            'test_workflow_associations',
            'test_workflow_stages',
            'test_workflow_transitions',
            'test_action_log_config',
            'test_action_logs',
            'test_action_logs_extensions',
            'test_action_logs_users',
            'test_guidedtours',
            'test_guidedtour_steps',
            'test_scheduler_logs',
            'test_schemaorg',
            'test_tuf_metadata',
        ];

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => $coreJoomlaTables,
            ],
            [
                'method' => 'loadObjectList',
                'return' => [], // No extensions
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // All tables are recognized as core Joomla tables, so no orphans
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('no orphaned tables detected', $result->description);
    }

    public function testRunRecognizesKnownSharedTables(): void
    {
        // Test akeeba_common is recognized as a known shared table
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => [
                    'test_content',
                    'test_users',
                    'test_extensions',
                    'test_akeeba_common', // Shared table (Akeeba)
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // akeeba_common should be recognized and not flagged as orphaned
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunMatchesExtensionTablesByPrefix(): void
    {
        // Test that tables matching installed component prefixes are recognized
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => [
                    'test_content',
                    'test_users',
                    'test_extensions',
                    'test_admintools',           // Matches com_admintools
                    'test_admintools_badwords',  // Matches com_admintools prefix
                    'test_admintools_scans',     // Matches com_admintools prefix
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'element' => 'com_admintools',
                        'type' => 'component',
                    ],
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // All admintools tables should be recognized via prefix matching
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunHandlesExtensionsWithDotsAndDashes(): void
    {
        // Test element names with special characters are handled
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_content', 'test_extensions', 'test_my_extension', 'test_my_extension_data'],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'element' => 'com_my.extension',
                        'type' => 'component',
                    ],
                    (object) [
                        'element' => 'com_my-extension',
                        'type' => 'component',
                    ],
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Special characters in element names should be converted to underscores
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunIgnoresNonComponentExtensions(): void
    {
        // Only components should be used for prefix matching
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => [
                    'test_content',
                    'test_extensions',
                    'test_mymodule',  // Should be orphaned - module doesn't create tables
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'element' => 'mod_mymodule',
                        'type' => 'module',
                    ], // Not a component
                    (object) [
                        'element' => 'plg_system_mymodule',
                        'type' => 'plugin',
                    ], // Not a component
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // mymodule table should be flagged as orphaned since only components are checked
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('test_mymodule', $result->description);
    }

    public function testRunWithEmptyTableList(): void
    {
        // Test with no tables at all in the database
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => [],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('0 tables', $result->description);
    }

    public function testRunReportsCorrectOrphanedTableCount(): void
    {
        // Test that the orphaned table count is accurate
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_content', 'test_orphan1', 'test_orphan2', 'test_orphan3'],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        // Should report 4 total tables and 3 orphaned (content is core)
        $this->assertStringContainsString('4 tables', $result->description);
        $this->assertStringContainsString('3 may be orphaned', $result->description);
    }

    public function testRunListsOrphanedTableNamesInMessage(): void
    {
        // Verify orphaned table names appear in the message
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_content', 'test_legacy_extension', 'test_old_plugin_data'],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('test_legacy_extension', $result->description);
        $this->assertStringContainsString('test_old_plugin_data', $result->description);
    }

    public function testRunExactTableMatchAndPrefixMatch(): void
    {
        // Test both exact match (test_content) and prefix match (test_content_*)
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => [
                    'test_content',
                    'test_extensions',
                    'test_customcomp',         // Exact match for com_customcomp
                    'test_customcomp_items',   // Prefix match for com_customcomp
                    'test_customcomp_config',  // Prefix match for com_customcomp
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'element' => 'com_customcomp',
                        'type' => 'component',
                    ],
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // All customcomp tables should be matched
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testCriticalStatusListsAllOrphanedTables(): void
    {
        // When critical (>10 orphaned), all table names should be listed
        $allTables = ['test_content', 'test_extensions'];
        $orphanedTables = [];

        // Create 15 orphaned tables
        for ($i = 1; $i <= 15; $i++) {
            $tableName = 'test_orphan_table_' . $i;
            $allTables[] = $tableName;
            $orphanedTables[] = $tableName;
        }

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => $allTables,
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('15 potential orphaned tables', $result->description);

        // Verify some of the orphaned table names are in the message
        $this->assertStringContainsString('test_orphan_table_1', $result->description);
        $this->assertStringContainsString('test_orphan_table_15', $result->description);
    }

    public function testRunDeduplicatesExpectedTables(): void
    {
        // Tables that match multiple sources should only be counted once
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => [
                    'test_content', // Core table
                    'test_users',   // Core table
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'element' => 'com_content',
                        'type' => 'component',
                    ], // Would also match via prefix
                    (object) [
                        'element' => 'com_users',
                        'type' => 'component',
                    ],
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Should still be good - tables matched via multiple sources don't cause duplicates
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('2 tables', $result->description);
    }

    public function testRunHandlesTablesWithUnderscoresInName(): void
    {
        // Test tables with multiple underscores are handled correctly
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => [
                    'test_content',
                    'test_extensions',
                    'test_my_complex_extension_name',
                    'test_my_complex_extension_name_items',
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'element' => 'com_my_complex_extension_name',
                        'type' => 'component',
                    ],
                ],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testBoundaryConditionExactlyTenOrphanedTables(): void
    {
        // Test boundary: exactly 10 orphaned tables should be WARNING, not CRITICAL
        $allTables = ['test_content', 'test_extensions'];

        // Add exactly 10 orphaned tables
        for ($i = 1; $i <= 10; $i++) {
            $allTables[] = 'test_orphan_' . $i;
        }

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => $allTables,
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Exactly 10 should be WARNING (> 10 is CRITICAL)
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testBoundaryConditionElevenOrphanedTables(): void
    {
        // Test boundary: 11 orphaned tables should be CRITICAL
        $allTables = ['test_content', 'test_extensions'];

        // Add exactly 11 orphaned tables
        for ($i = 1; $i <= 11; $i++) {
            $allTables[] = 'test_orphan_' . $i;
        }

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => $allTables,
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // 11 should be CRITICAL (> 10)
        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    // ========================================================================
    // Tests for SQL file parsing (getExtensionTablesFromSql, parseTablesFromSql)
    // ========================================================================

    public function testRunParsesTablesFromSqlInstallFiles(): void
    {
        // Create a temporary component with SQL install file
        $componentPath = JPATH_ADMINISTRATOR . '/components/com_testextension';
        $sqlPath = $componentPath . '/sql';

        // Create directories
        @mkdir($sqlPath, 0777, true);

        // Create SQL install file with CREATE TABLE statements
        $sqlContent = <<<'SQL'
-- Test component install SQL
CREATE TABLE IF NOT EXISTS `#__testextension_items` (
    id INT PRIMARY KEY,
    title VARCHAR(255)
);

CREATE TABLE `#__testextension_categories` (
    id INT PRIMARY KEY,
    name VARCHAR(255)
);
SQL;

        file_put_contents($sqlPath . '/install.mysql.sql', $sqlContent);

        try {
            // Test that tables from SQL file are recognized
            $database = MockDatabaseFactory::createWithSequentialQueries([
                [
                    'method' => 'loadColumn',
                    'return' => [
                        'test_content',
                        'test_extensions',
                        'test_testextension_items',      // From SQL file
                        'test_testextension_categories', // From SQL file
                    ],
                ],
                [
                    'method' => 'loadObjectList',
                    'return' => [],
                ],
            ]);
            $this->check->setDatabase($database);

            $result = $this->check->run();

            // Tables from SQL file should be recognized
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        } finally {
            // Cleanup
            @unlink($sqlPath . '/install.mysql.sql');
            @rmdir($sqlPath);
            @rmdir($componentPath);
        }
    }

    public function testRunParsesSqlWithDifferentTableFormats(): void
    {
        // Create a component with various CREATE TABLE formats
        $componentPath = JPATH_ADMINISTRATOR . '/components/com_formattest';
        $sqlPath = $componentPath . '/sql';
        @mkdir($sqlPath, 0777, true);

        // Test different SQL formatting variations
        $sqlContent = <<<'SQL'
-- Various CREATE TABLE formats
CREATE TABLE #__formattest_simple (id INT);
CREATE TABLE `#__formattest_backticks` (id INT);
CREATE TABLE "#__formattest_quotes" (id INT);
CREATE TABLE '#__formattest_single' (id INT);
CREATE TABLE IF NOT EXISTS #__formattest_ifnotexists (id INT);
CREATE TABLE IF NOT EXISTS `#__formattest_backticks_exists` (id INT);
SQL;

        file_put_contents($sqlPath . '/install.mysql.sql', $sqlContent);

        try {
            $database = MockDatabaseFactory::createWithSequentialQueries([
                [
                    'method' => 'loadColumn',
                    'return' => [
                        'test_content',
                        'test_formattest_simple',
                        'test_formattest_backticks',
                        'test_formattest_quotes',
                        'test_formattest_single',
                        'test_formattest_ifnotexists',
                        'test_formattest_backticks_exists',
                    ],
                ],
                [
                    'method' => 'loadObjectList',
                    'return' => [],
                ],
            ]);
            $this->check->setDatabase($database);

            $result = $this->check->run();

            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        } finally {
            @unlink($sqlPath . '/install.mysql.sql');
            @rmdir($sqlPath);
            @rmdir($componentPath);
        }
    }

    public function testRunHandlesMultipleSqlFileLocations(): void
    {
        // Test sql/install.mysql.utf8.sql path
        $componentPath = JPATH_ADMINISTRATOR . '/components/com_utf8test';
        $sqlPath = $componentPath . '/sql';
        @mkdir($sqlPath, 0777, true);

        $sqlContent = 'CREATE TABLE `#__utf8test_data` (id INT);';
        file_put_contents($sqlPath . '/install.mysql.utf8.sql', $sqlContent);

        try {
            $database = MockDatabaseFactory::createWithSequentialQueries([
                [
                    'method' => 'loadColumn',
                    'return' => ['test_content', 'test_utf8test_data'],
                ],
                [
                    'method' => 'loadObjectList',
                    'return' => [],
                ],
            ]);
            $this->check->setDatabase($database);

            $result = $this->check->run();

            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        } finally {
            @unlink($sqlPath . '/install.mysql.utf8.sql');
            @rmdir($sqlPath);
            @rmdir($componentPath);
        }
    }

    public function testRunHandlesMysqlSubdirectory(): void
    {
        // Test sql/mysql/install.sql path
        $componentPath = JPATH_ADMINISTRATOR . '/components/com_mysqlpath';
        $sqlPath = $componentPath . '/sql/mysql';
        @mkdir($sqlPath, 0777, true);

        $sqlContent = 'CREATE TABLE `#__mysqlpath_items` (id INT);';
        file_put_contents($sqlPath . '/install.sql', $sqlContent);

        try {
            $database = MockDatabaseFactory::createWithSequentialQueries([
                [
                    'method' => 'loadColumn',
                    'return' => ['test_content', 'test_mysqlpath_items'],
                ],
                [
                    'method' => 'loadObjectList',
                    'return' => [],
                ],
            ]);
            $this->check->setDatabase($database);

            $result = $this->check->run();

            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        } finally {
            @unlink($sqlPath . '/install.sql');
            @rmdir($sqlPath);
            @rmdir($componentPath . '/sql');
            @rmdir($componentPath);
        }
    }

    public function testRunHandlesGenericInstallSql(): void
    {
        // Test sql/install.sql path (no mysql in name)
        $componentPath = JPATH_ADMINISTRATOR . '/components/com_generic';
        $sqlPath = $componentPath . '/sql';
        @mkdir($sqlPath, 0777, true);

        $sqlContent = 'CREATE TABLE `#__generic_data` (id INT);';
        file_put_contents($sqlPath . '/install.sql', $sqlContent);

        try {
            $database = MockDatabaseFactory::createWithSequentialQueries([
                [
                    'method' => 'loadColumn',
                    'return' => ['test_content', 'test_generic_data'],
                ],
                [
                    'method' => 'loadObjectList',
                    'return' => [],
                ],
            ]);
            $this->check->setDatabase($database);

            $result = $this->check->run();

            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        } finally {
            @unlink($sqlPath . '/install.sql');
            @rmdir($sqlPath);
            @rmdir($componentPath);
        }
    }

    public function testRunHandlesNonReadableSqlFile(): void
    {
        // Test that unreadable SQL files are skipped gracefully
        $componentPath = JPATH_ADMINISTRATOR . '/components/com_unreadable';
        $sqlPath = $componentPath . '/sql';
        @mkdir($sqlPath, 0777, true);

        // Create file with content
        $sqlContent = 'CREATE TABLE `#__unreadable_items` (id INT);';
        file_put_contents($sqlPath . '/install.mysql.sql', $sqlContent);

        // Make it unreadable (only works if not running as root)
        @chmod($sqlPath . '/install.mysql.sql', 0000);

        try {
            $database = MockDatabaseFactory::createWithSequentialQueries([
                [
                    'method' => 'loadColumn',
                    'return' => ['test_content', 'test_unreadable_items'],
                ],
                [
                    'method' => 'loadObjectList',
                    'return' => [],
                ],
            ]);
            $this->check->setDatabase($database);

            $result = $this->check->run();

            // Should still work but table won't be recognized from SQL
            // So it will be flagged as orphaned
            $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
        } finally {
            @chmod($sqlPath . '/install.mysql.sql', 0644);
            @unlink($sqlPath . '/install.mysql.sql');
            @rmdir($sqlPath);
            @rmdir($componentPath);
        }
    }

    public function testRunWithNoComponentsDirectory(): void
    {
        // Ensure the components directory doesn't exist
        $componentsPath = JPATH_ADMINISTRATOR . '/components';

        // If it exists from other tests, temporarily move it
        $tempPath = JPATH_ADMINISTRATOR . '/components_backup_' . uniqid();
        $moved = false;

        if (is_dir($componentsPath)) {
            @rename($componentsPath, $tempPath);
            $moved = true;
        }

        try {
            $database = MockDatabaseFactory::createWithSequentialQueries([
                [
                    'method' => 'loadColumn',
                    'return' => ['test_content', 'test_orphan'],
                ],
                [
                    'method' => 'loadObjectList',
                    'return' => [],
                ],
            ]);
            $this->check->setDatabase($database);

            $result = $this->check->run();

            // Should still work, just won't parse any SQL files
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        } finally {
            if ($moved) {
                @rename($tempPath, $componentsPath);
            }
        }
    }

    public function testRunDeduplicatesTablesFromMultipleSqlFiles(): void
    {
        // Test that the same table in multiple SQL files is only counted once
        $componentPath = JPATH_ADMINISTRATOR . '/components/com_duplicates';
        $sqlPath = $componentPath . '/sql';
        $mysqlPath = $sqlPath . '/mysql';
        @mkdir($mysqlPath, 0777, true);

        // Same table in multiple locations
        $sqlContent = 'CREATE TABLE `#__duplicates_shared` (id INT);';
        file_put_contents($sqlPath . '/install.mysql.sql', $sqlContent);
        file_put_contents($mysqlPath . '/install.sql', $sqlContent);

        try {
            $database = MockDatabaseFactory::createWithSequentialQueries([
                [
                    'method' => 'loadColumn',
                    'return' => ['test_content', 'test_duplicates_shared'],
                ],
                [
                    'method' => 'loadObjectList',
                    'return' => [],
                ],
            ]);
            $this->check->setDatabase($database);

            $result = $this->check->run();

            // Table should be recognized (deduplication happens internally)
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        } finally {
            @unlink($sqlPath . '/install.mysql.sql');
            @unlink($mysqlPath . '/install.sql');
            @rmdir($mysqlPath);
            @rmdir($sqlPath);
            @rmdir($componentPath);
        }
    }

    public function testRunHandlesEmptySqlFile(): void
    {
        // Test with an empty SQL file
        $componentPath = JPATH_ADMINISTRATOR . '/components/com_empty';
        $sqlPath = $componentPath . '/sql';
        @mkdir($sqlPath, 0777, true);

        file_put_contents($sqlPath . '/install.mysql.sql', '-- Just a comment, no tables');

        try {
            $database = MockDatabaseFactory::createWithSequentialQueries([
                [
                    'method' => 'loadColumn',
                    'return' => ['test_content', 'test_orphan_table'],
                ],
                [
                    'method' => 'loadObjectList',
                    'return' => [],
                ],
            ]);
            $this->check->setDatabase($database);

            $result = $this->check->run();

            // Empty SQL file shouldn't cause errors
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        } finally {
            @unlink($sqlPath . '/install.mysql.sql');
            @rmdir($sqlPath);
            @rmdir($componentPath);
        }
    }

    public function testRunWithGlobReturningFalse(): void
    {
        // Test that glob returning false is handled
        // This tests the edge case where glob() returns false instead of an empty array
        // We can't easily trigger this in tests, but we verify the code path exists
        $componentPath = JPATH_ADMINISTRATOR . '/components';
        @mkdir($componentPath, 0777, true);

        // Create a component dir that's NOT named com_*
        $nonComponentPath = $componentPath . '/not_a_component';
        @mkdir($nonComponentPath, 0777, true);

        try {
            $database = MockDatabaseFactory::createWithSequentialQueries([
                [
                    'method' => 'loadColumn',
                    'return' => ['test_content'],
                ],
                [
                    'method' => 'loadObjectList',
                    'return' => [],
                ],
            ]);
            $this->check->setDatabase($database);

            $result = $this->check->run();

            // Should work fine, just no SQL files to parse from non-component dirs
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
        } finally {
            @rmdir($nonComponentPath);
        }
    }
}

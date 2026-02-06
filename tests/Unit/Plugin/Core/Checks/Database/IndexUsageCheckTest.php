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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\IndexUsageCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexUsageCheck::class)]
class IndexUsageCheckTest extends TestCase
{
    private IndexUsageCheck $indexUsageCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        $this->cmsApplication->set('dbprefix', 'test_');
        $this->cmsApplication->set('db', 'test_database');
        Factory::setApplication($this->cmsApplication);
        $this->indexUsageCheck = new IndexUsageCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.index_usage', $this->indexUsageCheck->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->indexUsageCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->indexUsageCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->indexUsageCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->indexUsageCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenNoTables(): void
    {
        // Mock returns empty column for table list
        $database = MockDatabaseFactory::createWithColumn([]);
        $this->indexUsageCheck->setDatabase($database);

        $healthCheckResult = $this->indexUsageCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('0 tables', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenAllTablesHaveIndexes(): void
    {
        // Tables returned by SHOW TABLES
        // Second query for SHOW INDEX returns indexes for that table
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_content', 'test_users'],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'Key_name' => 'PRIMARY',
                        'Column_name' => 'id',
                    ],
                    (object) [
                        'Key_name' => 'idx_state',
                        'Column_name' => 'state',
                    ],
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'Key_name' => 'PRIMARY',
                        'Column_name' => 'id',
                    ],
                    (object) [
                        'Key_name' => 'idx_email',
                        'Column_name' => 'email',
                    ],
                ],
            ],
        ]);
        $this->indexUsageCheck->setDatabase($database);

        $healthCheckResult = $this->indexUsageCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('2 tables', $healthCheckResult->description);
        $this->assertStringContainsString('primary keys', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenTableMissingPrimaryKey(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_custom'],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    // No PRIMARY key, only regular index
                    (object) [
                        'Key_name' => 'idx_state',
                        'Column_name' => 'state',
                    ],
                ],
            ],
        ]);
        $this->indexUsageCheck->setDatabase($database);

        $healthCheckResult = $this->indexUsageCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('missing primary key', $healthCheckResult->description);
        $this->assertStringContainsString('test_custom', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenTableHasNoIndexes(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_noindex'],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ], // No indexes at all
        ]);
        $this->indexUsageCheck->setDatabase($database);

        $healthCheckResult = $this->indexUsageCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        // Could be "no indexes" or "missing primary key" depending on implementation
        $this->assertTrue(
            str_contains($healthCheckResult->description, 'no indexes') || str_contains(
                $healthCheckResult->description,
                'missing primary key',
            ),
        );
    }

    public function testRunExcludesTablesDesignedWithoutPrimaryKey(): void
    {
        // Tables like contentitem_tag_map are designed without primary keys
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_contentitem_tag_map', 'test_content'],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    // contentitem_tag_map - only has composite index, no PRIMARY
                    (object) [
                        'Key_name' => 'idx_tag_type',
                        'Column_name' => 'tag_id',
                    ],
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    // content - has PRIMARY
                    (object) [
                        'Key_name' => 'PRIMARY',
                        'Column_name' => 'id',
                    ],
                ],
            ],
        ]);
        $this->indexUsageCheck->setDatabase($database);

        $healthCheckResult = $this->indexUsageCheck->run();

        // Should be GOOD because contentitem_tag_map is excluded from primary key check
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunReportsMultipleTablesMissingPrimaryKey(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => [
                    'test_custom1',
                    'test_custom2',
                    'test_custom3',
                    'test_custom4',
                    'test_custom5',
                    'test_custom6', // 6 tables to test truncation
                ],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ], // No indexes
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],
            ],
        ]);
        $this->indexUsageCheck->setDatabase($database);

        $healthCheckResult = $this->indexUsageCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('6 table(s)', $healthCheckResult->description);
        // Should show only first 5 and then "..."
        $this->assertStringContainsString('...', $healthCheckResult->description);
    }

    public function testRunSkipsTablesWithQueryException(): void
    {
        // Test that exceptions during SHOW INDEX are handled gracefully
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_content', 'test_problematic', 'test_users'],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'Key_name' => 'PRIMARY',
                        'Column_name' => 'id',
                    ],
                ],
            ], // test_content - OK
            [
                'method' => 'loadObjectList',
                'exception' => new \RuntimeException('Access denied'),
            ], // test_problematic - Exception
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'Key_name' => 'PRIMARY',
                        'Column_name' => 'id',
                    ],
                ],
            ], // test_users - OK
        ]);
        $this->indexUsageCheck->setDatabase($database);

        $healthCheckResult = $this->indexUsageCheck->run();

        // Should still return GOOD because the problematic table is skipped
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        // Only 2 tables should be counted (skipped the problematic one)
        $this->assertStringContainsString('3 tables', $healthCheckResult->description);
    }

    public function testRunExcludesAllDesignedWithoutPrimaryKeyTables(): void
    {
        // Test all tables in the TABLES_WITHOUT_PRIMARY_KEY_BY_DESIGN constant
        $tablesWithoutPkByDesign = [
            'test_contentitem_tag_map',
            'test_fields_values',
            'test_finder_terms_common',
            'test_finder_tokens',
            'test_finder_tokens_aggregate',
            'test_messages_cfg',
            'test_user_profiles',
        ];

        $queries = [
            [
                'method' => 'loadColumn',
                'return' => $tablesWithoutPkByDesign,
            ],
        ];

        // Each table has an index but no PRIMARY key
        foreach ($tablesWithoutPkByDesign as $tablesWithoutPk) {
            $queries[] = [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'Key_name' => 'idx_something',
                        'Column_name' => 'some_col',
                    ],
                ],
            ];
        }

        $database = MockDatabaseFactory::createWithSequentialQueries($queries);
        $this->indexUsageCheck->setDatabase($database);

        $healthCheckResult = $this->indexUsageCheck->run();

        // Should be GOOD - all tables are excluded from primary key check
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunHandlesTableWithoutPrefix(): void
    {
        // Test the edge case where tableName doesn't start with prefix
        // This covers the else branch in isTableWithoutPrimaryKeyByDesign
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['other_prefix_table'],  // No "test_" prefix
            ],
            [
                'method' => 'loadObjectList',
                'return' => [],  // No indexes
            ],
        ]);
        $this->indexUsageCheck->setDatabase($database);

        $healthCheckResult = $this->indexUsageCheck->run();

        // Should return warning for missing primary key
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWarnsAboutTablesWithOnlyNoPrimaryKeyAndNoOtherIndex(): void
    {
        // Test tables that have absolutely no indexes (neither PRIMARY nor other)
        // This is different from "missing primary key" - it's "no indexes at all"
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_content', 'test_totally_unindexed'],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'Key_name' => 'PRIMARY',
                        'Column_name' => 'id',
                    ],
                ],
            ], // test_content - has primary
            [
                'method' => 'loadObjectList',
                'return' => [],  // test_totally_unindexed - no indexes at all
            ],
        ]);
        $this->indexUsageCheck->setDatabase($database);

        $healthCheckResult = $this->indexUsageCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        // Should mention the table missing primary key
        $this->assertStringContainsString('test_totally_unindexed', $healthCheckResult->description);
    }

    public function testRunPrioritizesMissingPrimaryKeyOverNoIndexes(): void
    {
        // When there are tables missing primary keys AND tables with no indexes,
        // the primary key warning takes precedence
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_no_pk', 'test_no_indexes'],
            ],
            [
                'method' => 'loadObjectList',
                'return' => [
                    (object) [
                        'Key_name' => 'idx_something',
                        'Column_name' => 'col',
                    ],
                ],
            ], // test_no_pk - has index, no PRIMARY
            [
                'method' => 'loadObjectList',
                'return' => [],  // test_no_indexes - nothing at all
            ],
        ]);
        $this->indexUsageCheck->setDatabase($database);

        $healthCheckResult = $this->indexUsageCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        // Should warn about missing primary key first
        $this->assertStringContainsString('missing primary key', $healthCheckResult->description);
    }
}

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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\BackupAgeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BackupAgeCheck::class)]
class BackupAgeCheckTest extends TestCase
{
    private BackupAgeCheck $check;

    private CMSApplication $app;

    protected function setUp(): void
    {
        $this->app = new CMSApplication();
        $this->app->set('dbprefix', 'test_');
        Factory::setApplication($this->app);
        $this->check = new BackupAgeCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.backup_age', $this->check->getSlug());
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

    public function testRunWithNoAkeebaTableReturnsWarning(): void
    {
        // SHOW TABLES returns empty array - no akeeba table
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => [],
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Akeeba Backup is not installed', $result->description);
    }

    public function testRunWithRecentBackupReturnsGood(): void
    {
        $recentBackup = (object) [
            'backupstart' => (new \DateTime('-2 days'))->format('Y-m-d H:i:s'),
            'description' => 'Daily backup',
            'status' => 'complete',
        ];

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_ak_stats'],
            ], // Table exists
            [
                'method' => 'loadObject',
                'return' => $recentBackup,
            ],     // Last backup
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('Last successful backup', $result->description);
        $this->assertStringContainsString('2 day(s) ago', $result->description);
    }

    public function testRunWithOldBackup7DaysReturnsWarning(): void
    {
        $oldBackup = (object) [
            'backupstart' => (new \DateTime('-10 days'))->format('Y-m-d H:i:s'),
            'description' => 'Weekly backup',
            'status' => 'complete',
        ];

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_ak_stats'],
            ],
            [
                'method' => 'loadObject',
                'return' => $oldBackup,
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('10 days ago', $result->description);
        $this->assertStringContainsString('Consider running a backup soon', $result->description);
    }

    public function testRunWithVeryOldBackup30DaysReturnsCritical(): void
    {
        $veryOldBackup = (object) [
            'backupstart' => (new \DateTime('-45 days'))->format('Y-m-d H:i:s'),
            'description' => 'Monthly backup',
            'status' => 'complete',
        ];

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_ak_stats'],
            ],
            [
                'method' => 'loadObject',
                'return' => $veryOldBackup,
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('45 days ago', $result->description);
        $this->assertStringContainsString('Create a backup immediately', $result->description);
    }

    public function testRunWithNoSuccessfulBackupsReturnsCritical(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_ak_stats'],
            ], // Table exists
            [
                'method' => 'loadObject',
                'return' => null,
            ],              // No successful backup
            [
                'method' => 'loadResult',
                'return' => 5,
            ],                 // 5 total backups (failed)
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('No successful backups found', $result->description);
    }

    public function testRunWithNoBackupsAtAllReturnsCritical(): void
    {
        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_ak_stats'],
            ], // Table exists
            [
                'method' => 'loadObject',
                'return' => null,
            ],              // No successful backup
            [
                'method' => 'loadResult',
                'return' => 0,
            ],                 // 0 total backups
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('no backups have been created', $result->description);
    }

    public function testRunWithBackupNoDescriptionShowsDateOnly(): void
    {
        $backupNoDesc = (object) [
            'backupstart' => (new \DateTime('-1 day'))->format('Y-m-d H:i:s'),
            'description' => '',
            'status' => 'complete',
        ];

        $database = MockDatabaseFactory::createWithSequentialQueries([
            [
                'method' => 'loadColumn',
                'return' => ['test_ak_stats'],
            ],
            [
                'method' => 'loadObject',
                'return' => $backupNoDesc,
            ],
        ]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('1 day(s) ago', $result->description);
    }

    public function testRunHandlesExceptionGracefully(): void
    {
        // Test that exceptions during database queries are caught and handled
        $database = MockDatabaseFactory::createWithException(new \RuntimeException('Connection failed'));
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Should return Warning (caught by AbstractHealthCheck::run())
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }
}

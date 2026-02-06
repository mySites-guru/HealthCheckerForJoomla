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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\TableCharsetCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableCharsetCheck::class)]
class TableCharsetCheckTest extends TestCase
{
    private TableCharsetCheck $tableCharsetCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        $this->cmsApplication->set('dbprefix', 'test_');
        $this->cmsApplication->set('db', 'test_database');
        Factory::setApplication($this->cmsApplication);
        $this->tableCharsetCheck = new TableCharsetCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.table_charset', $this->tableCharsetCheck->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->tableCharsetCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->tableCharsetCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->tableCharsetCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->tableCharsetCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenAllTablesUseUtf8mb4(): void
    {
        // Empty array means no tables with non-utf8mb4 collation
        $database = MockDatabaseFactory::createWithObjectList([]);
        $this->tableCharsetCheck->setDatabase($database);

        $healthCheckResult = $this->tableCharsetCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('utf8mb4', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenTablesUseOldCollation(): void
    {
        $table1 = new \stdClass();
        $table1->TABLE_NAME = 'test_content';
        $table1->TABLE_COLLATION = 'utf8_general_ci';

        $table2 = new \stdClass();
        $table2->TABLE_NAME = 'test_users';
        $table2->TABLE_COLLATION = 'latin1_swedish_ci';

        $database = MockDatabaseFactory::createWithObjectList([$table1, $table2]);
        $this->tableCharsetCheck->setDatabase($database);

        $healthCheckResult = $this->tableCharsetCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('2 table(s)', $healthCheckResult->description);
    }
}

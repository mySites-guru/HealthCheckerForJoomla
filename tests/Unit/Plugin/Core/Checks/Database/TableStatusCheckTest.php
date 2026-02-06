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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\TableStatusCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableStatusCheck::class)]
class TableStatusCheckTest extends TestCase
{
    private TableStatusCheck $tableStatusCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        $this->cmsApplication->set('dbprefix', 'test_');
        Factory::setApplication($this->cmsApplication);
        $this->tableStatusCheck = new TableStatusCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.table_status', $this->tableStatusCheck->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->tableStatusCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->tableStatusCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->tableStatusCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->tableStatusCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenTablesAreHealthy(): void
    {
        $table1 = new \stdClass();
        $table1->Name = 'test_content';
        $table1->Engine = 'InnoDB';
        $table1->Comment = '';
        $table1->Data_length = 1048576;
        $table1->Index_length = 524288;

        $table2 = new \stdClass();
        $table2->Name = 'test_users';
        $table2->Engine = 'InnoDB';
        $table2->Comment = '';
        $table2->Data_length = 2097152;
        $table2->Index_length = 1048576;

        $database = MockDatabaseFactory::createWithObjectList([$table1, $table2]);
        $this->tableStatusCheck->setDatabase($database);

        $healthCheckResult = $this->tableStatusCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('2 tables', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenTableIsCorrupted(): void
    {
        $table1 = new \stdClass();
        $table1->Name = 'test_content';
        $table1->Engine = 'InnoDB';
        $table1->Comment = 'Corrupt';
        $table1->Data_length = 1048576;
        $table1->Index_length = 524288;

        $database = MockDatabaseFactory::createWithObjectList([$table1]);
        $this->tableStatusCheck->setDatabase($database);

        $healthCheckResult = $this->tableStatusCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('corrupted', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenEngineIsNull(): void
    {
        $table1 = new \stdClass();
        $table1->Name = 'test_content';
        $table1->Engine = null;
        $table1->Comment = '';
        $table1->Data_length = 1048576;
        $table1->Index_length = 524288;

        $database = MockDatabaseFactory::createWithObjectList([$table1]);
        $this->tableStatusCheck->setDatabase($database);

        $healthCheckResult = $this->tableStatusCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }
}

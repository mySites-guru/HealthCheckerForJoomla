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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\TableEngineCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TableEngineCheck::class)]
class TableEngineCheckTest extends TestCase
{
    private TableEngineCheck $tableEngineCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        $this->cmsApplication->set('dbprefix', 'test_');
        $this->cmsApplication->set('db', 'test_database');
        Factory::setApplication($this->cmsApplication);
        $this->tableEngineCheck = new TableEngineCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.table_engine', $this->tableEngineCheck->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->tableEngineCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->tableEngineCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->tableEngineCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->tableEngineCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenAllTablesUseInnoDB(): void
    {
        // Empty array means no tables with non-InnoDB engine
        $database = MockDatabaseFactory::createWithObjectList([]);
        $this->tableEngineCheck->setDatabase($database);

        $healthCheckResult = $this->tableEngineCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('InnoDB', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenTablesUseMyISAM(): void
    {
        $table1 = new \stdClass();
        $table1->TABLE_NAME = 'test_content';
        $table1->ENGINE = 'MyISAM';

        $database = MockDatabaseFactory::createWithObjectList([$table1]);
        $this->tableEngineCheck->setDatabase($database);

        $healthCheckResult = $this->tableEngineCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 table(s)', $healthCheckResult->description);
    }
}

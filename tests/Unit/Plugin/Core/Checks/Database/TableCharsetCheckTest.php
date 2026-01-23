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
    private TableCharsetCheck $check;

    private CMSApplication $app;

    protected function setUp(): void
    {
        $this->app = new CMSApplication();
        $this->app->set('dbprefix', 'test_');
        $this->app->set('db', 'test_database');
        Factory::setApplication($this->app);
        $this->check = new TableCharsetCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.table_charset', $this->check->getSlug());
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

    public function testRunReturnsGoodWhenAllTablesUseUtf8mb4(): void
    {
        // Empty array means no tables with non-utf8mb4 collation
        $database = MockDatabaseFactory::createWithObjectList([]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('utf8mb4', $result->description);
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
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('2 table(s)', $result->description);
    }
}

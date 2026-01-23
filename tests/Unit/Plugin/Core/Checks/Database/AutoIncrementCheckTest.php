<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Database;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\AutoIncrementCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AutoIncrementCheck::class)]
class AutoIncrementCheckTest extends TestCase
{
    private AutoIncrementCheck $check;

    private CMSApplication $app;

    protected function setUp(): void
    {
        $this->app = new CMSApplication();
        $this->app->set('dbprefix', 'jos_');
        Factory::setApplication($this->app);
        $this->check = new AutoIncrementCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.auto_increment', $this->check->getSlug());
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

    public function testRunReturnsGoodWhenAutoIncrementValuesLow(): void
    {
        $tables = [
            (object) ['Name' => 'jos_content', 'Auto_increment' => 100],
            (object) ['Name' => 'jos_users', 'Auto_increment' => 50],
        ];
        $database = MockDatabaseFactory::createWithObjectList($tables);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('sufficient headroom', $result->description);
    }

    public function testRunReturnsWarningWhenAutoIncrementValuesHigh(): void
    {
        // 80% of INT_MAX (2147483647) = 1717986918
        $tables = [
            (object) ['Name' => 'jos_content', 'Auto_increment' => 1800000000],
        ];
        $database = MockDatabaseFactory::createWithObjectList($tables);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('high', $result->description);
    }

    public function testRunSkipsTablesWithNullAutoIncrement(): void
    {
        $tables = [
            (object) ['Name' => 'jos_content', 'Auto_increment' => null],
            (object) ['Name' => 'jos_users', 'Auto_increment' => 50],
        ];
        $database = MockDatabaseFactory::createWithObjectList($tables);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }
}

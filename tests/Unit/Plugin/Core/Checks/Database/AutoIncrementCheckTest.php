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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\AutoIncrementCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AutoIncrementCheck::class)]
class AutoIncrementCheckTest extends TestCase
{
    private AutoIncrementCheck $autoIncrementCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        $this->cmsApplication->set('dbprefix', 'jos_');
        Factory::setApplication($this->cmsApplication);
        $this->autoIncrementCheck = new AutoIncrementCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.auto_increment', $this->autoIncrementCheck->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->autoIncrementCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->autoIncrementCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->autoIncrementCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->autoIncrementCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenAutoIncrementValuesLow(): void
    {
        $tables = [
            (object) [
                'Name' => 'jos_content',
                'Auto_increment' => 100,
            ],
            (object) [
                'Name' => 'jos_users',
                'Auto_increment' => 50,
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($tables);
        $this->autoIncrementCheck->setDatabase($database);

        $healthCheckResult = $this->autoIncrementCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('sufficient headroom', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenAutoIncrementValuesHigh(): void
    {
        // 80% of INT_MAX (2147483647) = 1717986918
        $tables = [
            (object) [
                'Name' => 'jos_content',
                'Auto_increment' => 1800000000,
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($tables);
        $this->autoIncrementCheck->setDatabase($database);

        $healthCheckResult = $this->autoIncrementCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('high', $healthCheckResult->description);
    }

    public function testRunSkipsTablesWithNullAutoIncrement(): void
    {
        $tables = [
            (object) [
                'Name' => 'jos_content',
                'Auto_increment' => null,
            ],
            (object) [
                'Name' => 'jos_users',
                'Auto_increment' => 50,
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($tables);
        $this->autoIncrementCheck->setDatabase($database);

        $healthCheckResult = $this->autoIncrementCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }
}

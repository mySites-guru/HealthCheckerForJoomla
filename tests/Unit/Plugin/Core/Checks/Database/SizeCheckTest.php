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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\SizeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SizeCheck::class)]
class SizeCheckTest extends TestCase
{
    private SizeCheck $check;

    private CMSApplication $app;

    protected function setUp(): void
    {
        $this->app = new CMSApplication();
        $this->app->set('dbprefix', 'jos_');
        Factory::setApplication($this->app);
        $this->check = new SizeCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.size', $this->check->getSlug());
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

    public function testRunReturnsGoodWhenDatabaseSizeSmall(): void
    {
        // Small database - 10MB each table
        $tables = [
            (object) ['Name' => 'jos_content', 'Data_length' => 5 * 1024 * 1024, 'Index_length' => 5 * 1024 * 1024],
            (object) ['Name' => 'jos_users', 'Data_length' => 1 * 1024 * 1024, 'Index_length' => 1 * 1024 * 1024],
        ];
        $database = MockDatabaseFactory::createWithObjectList($tables);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('healthy', $result->description);
    }

    public function testRunReturnsWarningWhenDatabaseSizeLarge(): void
    {
        // 1.5GB database
        $tables = [
            (object) ['Name' => 'jos_content', 'Data_length' => 750 * 1024 * 1024, 'Index_length' => 750 * 1024 * 1024],
        ];
        $database = MockDatabaseFactory::createWithObjectList($tables);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('getting large', $result->description);
    }

    public function testRunReturnsCriticalWhenDatabaseSizeVeryLarge(): void
    {
        // 6GB database
        $tables = [
            (object) ['Name' => 'jos_content', 'Data_length' => 3 * 1024 * 1024 * 1024, 'Index_length' => 3 * 1024 * 1024 * 1024],
        ];
        $database = MockDatabaseFactory::createWithObjectList($tables);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('very large', $result->description);
    }
}

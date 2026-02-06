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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\SizeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SizeCheck::class)]
class SizeCheckTest extends TestCase
{
    private SizeCheck $sizeCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        $this->cmsApplication->set('dbprefix', 'jos_');
        Factory::setApplication($this->cmsApplication);
        $this->sizeCheck = new SizeCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.size', $this->sizeCheck->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->sizeCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->sizeCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->sizeCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->sizeCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenDatabaseSizeSmall(): void
    {
        // Small database - 10MB each table
        $tables = [
            (object) [
                'Name' => 'jos_content',
                'Data_length' => 5 * 1024 * 1024,
                'Index_length' => 5 * 1024 * 1024,
            ],
            (object) [
                'Name' => 'jos_users',
                'Data_length' => 1024 * 1024,
                'Index_length' => 1024 * 1024,
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($tables);
        $this->sizeCheck->setDatabase($database);

        $healthCheckResult = $this->sizeCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('healthy', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenDatabaseSizeLarge(): void
    {
        // 1.5GB database
        $tables = [
            (object) [
                'Name' => 'jos_content',
                'Data_length' => 750 * 1024 * 1024,
                'Index_length' => 750 * 1024 * 1024,
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($tables);
        $this->sizeCheck->setDatabase($database);

        $healthCheckResult = $this->sizeCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('getting large', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenDatabaseSizeVeryLarge(): void
    {
        // 6GB database
        $tables = [
            (object) [
                'Name' => 'jos_content',
                'Data_length' => 3 * 1024 * 1024 * 1024,
                'Index_length' => 3 * 1024 * 1024 * 1024,
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($tables);
        $this->sizeCheck->setDatabase($database);

        $healthCheckResult = $this->sizeCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('very large', $healthCheckResult->description);
    }
}

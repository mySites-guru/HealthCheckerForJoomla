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
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Database\TablePrefixCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TablePrefixCheck::class)]
class TablePrefixCheckTest extends TestCase
{
    private TablePrefixCheck $tablePrefixCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        Factory::setApplication($this->cmsApplication);
        $this->tablePrefixCheck = new TablePrefixCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.table_prefix', $this->tablePrefixCheck->getSlug());
    }

    public function testGetCategoryReturnsDatabase(): void
    {
        $this->assertSame('database', $this->tablePrefixCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->tablePrefixCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->tablePrefixCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithUniquePrefixReturnsGood(): void
    {
        $this->cmsApplication->set('dbprefix', 'mysite_');

        $healthCheckResult = $this->tablePrefixCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('mysite_', $healthCheckResult->description);
    }

    public function testRunWithEmptyPrefixReturnsWarning(): void
    {
        $this->cmsApplication->set('dbprefix', '');

        $healthCheckResult = $this->tablePrefixCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('conflicts', $healthCheckResult->description);
    }

    public function testRunWithDefaultPrefixReturnsWarning(): void
    {
        $this->cmsApplication->set('dbprefix', 'jos_');

        $healthCheckResult = $this->tablePrefixCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('default', $healthCheckResult->description);
    }

    public function testRunWithShortPrefixReturnsWarning(): void
    {
        $this->cmsApplication->set('dbprefix', 'ab');

        $healthCheckResult = $this->tablePrefixCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('short', $healthCheckResult->description);
    }

    public function testRunWithThreeCharPrefixReturnsGood(): void
    {
        $this->cmsApplication->set('dbprefix', 'abc');

        $healthCheckResult = $this->tablePrefixCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }
}

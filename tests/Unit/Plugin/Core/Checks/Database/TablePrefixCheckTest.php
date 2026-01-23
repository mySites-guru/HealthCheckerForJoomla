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
    private TablePrefixCheck $check;

    private CMSApplication $app;

    protected function setUp(): void
    {
        $this->app = new CMSApplication();
        Factory::setApplication($this->app);
        $this->check = new TablePrefixCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('database.table_prefix', $this->check->getSlug());
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

    public function testRunWithUniquePrefixReturnsGood(): void
    {
        $this->app->set('dbprefix', 'mysite_');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('mysite_', $result->description);
    }

    public function testRunWithEmptyPrefixReturnsWarning(): void
    {
        $this->app->set('dbprefix', '');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('conflicts', $result->description);
    }

    public function testRunWithDefaultPrefixReturnsWarning(): void
    {
        $this->app->set('dbprefix', 'jos_');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('default', $result->description);
    }

    public function testRunWithShortPrefixReturnsWarning(): void
    {
        $this->app->set('dbprefix', 'ab');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('short', $result->description);
    }

    public function testRunWithThreeCharPrefixReturnsGood(): void
    {
        $this->app->set('dbprefix', 'abc');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }
}

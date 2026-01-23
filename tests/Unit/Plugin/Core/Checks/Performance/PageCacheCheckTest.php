<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Performance;

use Joomla\CMS\Plugin\PluginHelper;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\PageCacheCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PageCacheCheck::class)]
class PageCacheCheckTest extends TestCase
{
    private PageCacheCheck $check;

    protected function setUp(): void
    {
        PluginHelper::resetEnabled();
        $this->check = new PageCacheCheck();
    }

    protected function tearDown(): void
    {
        PluginHelper::resetEnabled();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.page_cache', $this->check->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $this->assertSame('performance', $this->check->getCategory());
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

    public function testRunReturnsWarningWhenPluginDisabled(): void
    {
        // PluginHelper::isEnabled returns false by default in stub
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('disabled', $result->description);
    }

    public function testRunReturnsGoodWhenPluginEnabled(): void
    {
        // Set the page cache plugin as enabled
        PluginHelper::setEnabled('system', 'pagecache', true);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('enabled', $result->description);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        // Test with plugin disabled
        $result = $this->check->run();
        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);

        // Test with plugin enabled
        PluginHelper::setEnabled('system', 'pagecache', true);
        $result = $this->check->run();
        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }
}

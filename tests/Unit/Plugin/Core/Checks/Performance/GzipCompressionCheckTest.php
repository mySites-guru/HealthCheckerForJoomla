<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Performance;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Performance\GzipCompressionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GzipCompressionCheck::class)]
class GzipCompressionCheckTest extends TestCase
{
    private GzipCompressionCheck $gzipCompressionCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        Factory::setApplication($this->cmsApplication);
        $this->gzipCompressionCheck = new GzipCompressionCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('performance.gzip_compression', $this->gzipCompressionCheck->getSlug());
    }

    public function testGetCategoryReturnsPerformance(): void
    {
        $this->assertSame('performance', $this->gzipCompressionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->gzipCompressionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->gzipCompressionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsWarningWhenGzipDisabled(): void
    {
        $this->cmsApplication->set('gzip', 0);

        $healthCheckResult = $this->gzipCompressionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenGzipEnabled(): void
    {
        $this->cmsApplication->set('gzip', 1);

        $healthCheckResult = $this->gzipCompressionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('enabled', $healthCheckResult->description);
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Seo;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\SefUrlsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SefUrlsCheck::class)]
class SefUrlsCheckTest extends TestCase
{
    private SefUrlsCheck $sefUrlsCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        Factory::setApplication($this->cmsApplication);
        $this->sefUrlsCheck = new SefUrlsCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.sef_urls', $this->sefUrlsCheck->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->sefUrlsCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->sefUrlsCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->sefUrlsCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithBothEnabledReturnsGood(): void
    {
        $this->cmsApplication->set('sef', 1);
        $this->cmsApplication->set('sef_rewrite', 1);

        $healthCheckResult = $this->sefUrlsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('enabled', $healthCheckResult->description);
    }

    public function testRunWithSefDisabledReturnsWarning(): void
    {
        $this->cmsApplication->set('sef', 0);
        $this->cmsApplication->set('sef_rewrite', 1);

        $healthCheckResult = $this->sefUrlsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', $healthCheckResult->description);
    }

    public function testRunWithRewriteDisabledReturnsWarning(): void
    {
        $this->cmsApplication->set('sef', 1);
        $this->cmsApplication->set('sef_rewrite', 0);

        $healthCheckResult = $this->sefUrlsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('rewriting is off', $healthCheckResult->description);
    }

    public function testRunWithBothDisabledReturnsWarning(): void
    {
        $this->cmsApplication->set('sef', 0);
        $this->cmsApplication->set('sef_rewrite', 0);

        $healthCheckResult = $this->sefUrlsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }
}

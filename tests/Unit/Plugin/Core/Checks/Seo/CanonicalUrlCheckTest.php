<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Seo;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\CanonicalUrlCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CanonicalUrlCheck::class)]
class CanonicalUrlCheckTest extends TestCase
{
    private CanonicalUrlCheck $canonicalUrlCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        Factory::setApplication($this->cmsApplication);
        $this->canonicalUrlCheck = new CanonicalUrlCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.canonical_url', $this->canonicalUrlCheck->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->canonicalUrlCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->canonicalUrlCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->canonicalUrlCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsWarningWhenSefDisabled(): void
    {
        $this->cmsApplication->set('sef', 0);

        $healthCheckResult = $this->canonicalUrlCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('SEF URLs are disabled', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenSefPluginDisabled(): void
    {
        $this->cmsApplication->set('sef', 1);
        $database = MockDatabaseFactory::createWithResult(0);
        $this->canonicalUrlCheck->setDatabase($database);

        $healthCheckResult = $this->canonicalUrlCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('SEF plugin', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenSefEnabledAndPluginActive(): void
    {
        $this->cmsApplication->set('sef', 1);
        $database = MockDatabaseFactory::createWithResult(1);
        $this->canonicalUrlCheck->setDatabase($database);

        $healthCheckResult = $this->canonicalUrlCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('SEF URLs are enabled', $healthCheckResult->description);
    }
}

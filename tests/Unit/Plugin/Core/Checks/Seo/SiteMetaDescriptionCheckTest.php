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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\SiteMetaDescriptionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SiteMetaDescriptionCheck::class)]
class SiteMetaDescriptionCheckTest extends TestCase
{
    private SiteMetaDescriptionCheck $siteMetaDescriptionCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        Factory::setApplication($this->cmsApplication);
        $this->siteMetaDescriptionCheck = new SiteMetaDescriptionCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.site_meta_description', $this->siteMetaDescriptionCheck->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->siteMetaDescriptionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->siteMetaDescriptionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->siteMetaDescriptionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithNoDescriptionReturnsWarning(): void
    {
        $this->cmsApplication->set('MetaDesc', '');

        $healthCheckResult = $this->siteMetaDescriptionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not set', $healthCheckResult->description);
    }

    public function testRunWithOptimalDescriptionReturnsGood(): void
    {
        $description = 'This is a well-crafted site meta description that provides a compelling ' .
                       'summary of the website content for search engine results.';
        $this->cmsApplication->set('MetaDesc', $description);

        $healthCheckResult = $this->siteMetaDescriptionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('characters', $healthCheckResult->description);
    }

    public function testRunWithShortDescriptionReturnsWarning(): void
    {
        $this->cmsApplication->set('MetaDesc', 'Short description');

        $healthCheckResult = $this->siteMetaDescriptionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('too short', $healthCheckResult->description);
    }

    public function testRunWithLongDescriptionReturnsWarning(): void
    {
        $longDescription = str_repeat('This is a very long description. ', 10);
        $this->cmsApplication->set('MetaDesc', $longDescription);

        $healthCheckResult = $this->siteMetaDescriptionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('too long', $healthCheckResult->description);
    }

    public function testRunWithZeroValueReturnsWarning(): void
    {
        $this->cmsApplication->set('MetaDesc', '0');

        $healthCheckResult = $this->siteMetaDescriptionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }
}

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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\MetaKeywordsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MetaKeywordsCheck::class)]
class MetaKeywordsCheckTest extends TestCase
{
    private MetaKeywordsCheck $metaKeywordsCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        Factory::setApplication($this->cmsApplication);
        $this->metaKeywordsCheck = new MetaKeywordsCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.meta_keywords', $this->metaKeywordsCheck->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->metaKeywordsCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->metaKeywordsCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->metaKeywordsCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithEmptyMetaKeywordsReturnsGood(): void
    {
        $this->cmsApplication->set('MetaKeys', '');

        $healthCheckResult = $this->metaKeywordsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not set', $healthCheckResult->description);
        $this->assertStringContainsString('2009', $healthCheckResult->description);
    }

    public function testRunWithMetaKeywordsSetReturnsGood(): void
    {
        $this->cmsApplication->set('MetaKeys', 'joomla, cms, website');

        $healthCheckResult = $this->metaKeywordsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Meta keywords are set', $healthCheckResult->description);
        $this->assertStringContainsString('no longer use', $healthCheckResult->description);
    }

    public function testRunAlwaysReturnsGood(): void
    {
        // This check always returns good status since meta keywords
        // are neither helpful nor harmful to SEO

        $this->cmsApplication->set('MetaKeys', 'test, keywords');
        $result = $this->metaKeywordsCheck->run();
        $this->assertSame(HealthStatus::Good, $result->healthStatus);

        $this->cmsApplication->set('MetaKeys', '');
        $result = $this->metaKeywordsCheck->run();
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithWhitespaceOnlyMetaKeywordsReturnsGoodAsNotSet(): void
    {
        $this->cmsApplication->set('MetaKeys', '   ');

        $healthCheckResult = $this->metaKeywordsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not set', $healthCheckResult->description);
    }
}

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
    private SiteMetaDescriptionCheck $check;

    private CMSApplication $app;

    protected function setUp(): void
    {
        $this->app = new CMSApplication();
        Factory::setApplication($this->app);
        $this->check = new SiteMetaDescriptionCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('seo.site_meta_description', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $this->assertSame('seo', $this->check->getCategory());
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

    public function testRunWithNoDescriptionReturnsWarning(): void
    {
        $this->app->set('MetaDesc', '');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('not set', $result->description);
    }

    public function testRunWithOptimalDescriptionReturnsGood(): void
    {
        $description = 'This is a well-crafted site meta description that provides a compelling ' .
                       'summary of the website content for search engine results.';
        $this->app->set('MetaDesc', $description);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('characters', $result->description);
    }

    public function testRunWithShortDescriptionReturnsWarning(): void
    {
        $this->app->set('MetaDesc', 'Short description');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('too short', $result->description);
    }

    public function testRunWithLongDescriptionReturnsWarning(): void
    {
        $longDescription = str_repeat('This is a very long description. ', 10);
        $this->app->set('MetaDesc', $longDescription);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('too long', $result->description);
    }

    public function testRunWithZeroValueReturnsWarning(): void
    {
        $this->app->set('MetaDesc', '0');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }
}

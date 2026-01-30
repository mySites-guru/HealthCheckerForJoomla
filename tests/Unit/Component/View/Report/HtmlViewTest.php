<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\View\Report;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;
use MySitesGuru\HealthChecker\Component\Administrator\View\Report\HtmlView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlView::class)]
class HtmlViewTest extends TestCase
{
    private ?CMSApplication $cmsApplication = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear toolbar instances
        Toolbar::clearInstances();

        // Store original app if set
        try {
            $this->cmsApplication = Factory::getApplication();
        } catch (\Exception) {
            $this->cmsApplication = null;
        }

        // Set up a mock application
        $cmsApplication = new CMSApplication();
        Factory::setApplication($cmsApplication);
    }

    protected function tearDown(): void
    {
        // Restore original application
        Factory::setApplication($this->cmsApplication);
        Toolbar::clearInstances();

        parent::tearDown();
    }

    public function testViewCanBeInstantiated(): void
    {
        $htmlView = new HtmlView();

        $this->assertInstanceOf(HtmlView::class, $htmlView);
    }

    public function testViewExtendsBaseHtmlView(): void
    {
        $htmlView = new HtmlView();

        $this->assertInstanceOf(\Joomla\CMS\MVC\View\HtmlView::class, $htmlView);
    }

    public function testBeforeReportHtmlDefaultsToEmptyString(): void
    {
        $htmlView = new HtmlView();

        $this->assertSame('', $htmlView->beforeReportHtml);
    }

    public function testBeforeReportHtmlIsPublic(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlView::class);
        $reflectionProperty = $reflectionClass->getProperty('beforeReportHtml');

        $this->assertTrue($reflectionProperty->isPublic());
    }

    public function testBeforeReportHtmlCanBeModified(): void
    {
        $htmlView = new HtmlView();
        $htmlView->beforeReportHtml = '<div>Custom content</div>';

        $this->assertSame('<div>Custom content</div>', $htmlView->beforeReportHtml);
    }

    public function testDisplayMethodExists(): void
    {
        $this->assertTrue(method_exists(HtmlView::class, 'display'));
    }

    public function testDisplayMethodAcceptsNullTemplate(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlView::class, 'display');
        $parameters = $reflectionMethod->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('tpl', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->allowsNull());
    }

    public function testAddToolbarMethodExists(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlView::class);

        $this->assertTrue($reflectionClass->hasMethod('addToolbar'));
    }

    public function testAddToolbarMethodIsProtected(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlView::class, 'addToolbar');

        $this->assertTrue($reflectionMethod->isProtected());
    }

    public function testViewHasCorrectNamespace(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlView::class);

        $this->assertSame(
            'MySitesGuru\HealthChecker\Component\Administrator\View\Report',
            $reflectionClass->getNamespaceName(),
        );
    }

    public function testViewIsNotAbstract(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlView::class);

        $this->assertFalse($reflectionClass->isAbstract());
    }

    public function testViewIsNotFinal(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlView::class);

        $this->assertFalse($reflectionClass->isFinal());
    }
}

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
use MySitesGuru\HealthChecker\Component\Administrator\View\Report\HtmlexportView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlexportView::class)]
class HtmlexportViewTest extends TestCase
{
    private ?CMSApplication $cmsApplication = null;

    protected function setUp(): void
    {
        parent::setUp();

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

        parent::tearDown();
    }

    public function testViewCanBeInstantiated(): void
    {
        $htmlexportView = new HtmlexportView();

        $this->assertInstanceOf(HtmlexportView::class, $htmlexportView);
    }

    public function testViewExtendsBaseHtmlView(): void
    {
        $htmlexportView = new HtmlexportView();

        $this->assertInstanceOf(\Joomla\CMS\MVC\View\HtmlView::class, $htmlexportView);
    }

    public function testDisplayMethodExists(): void
    {
        $this->assertTrue(method_exists(HtmlexportView::class, 'display'));
    }

    public function testDisplayMethodAcceptsNullTemplate(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'display');
        $parameters = $reflectionMethod->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('tpl', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->allowsNull());
    }

    public function testDisplayMethodReturnsVoid(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'display');
        $returnType = $reflectionMethod->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    public function testViewHasCorrectNamespace(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlexportView::class);

        $this->assertSame(
            'MySitesGuru\HealthChecker\Component\Administrator\View\Report',
            $reflectionClass->getNamespaceName(),
        );
    }

    public function testViewIsNotAbstract(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlexportView::class);

        $this->assertFalse($reflectionClass->isAbstract());
    }

    public function testViewIsNotFinal(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlexportView::class);

        $this->assertFalse($reflectionClass->isFinal());
    }

    public function testViewClassName(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlexportView::class);

        $this->assertSame('HtmlexportView', $reflectionClass->getShortName());
    }

    public function testRenderHtmlReportMethodExists(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlexportView::class);

        $this->assertTrue($reflectionClass->hasMethod('renderHtmlReport'));
    }

    public function testRenderHtmlReportMethodIsPrivate(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'renderHtmlReport');

        $this->assertTrue($reflectionMethod->isPrivate());
    }

    public function testRenderHtmlReportHasExpectedParameters(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'renderHtmlReport');
        $parameters = $reflectionMethod->getParameters();

        // Method has 12 parameters based on the source
        $this->assertCount(12, $parameters);

        // Check parameter names
        $paramNames = array_map(
            fn(\ReflectionParameter $reflectionParameter): string => $reflectionParameter->getName(),
            $parameters,
        );
        $this->assertContains('results', $paramNames);
        $this->assertContains('categories', $paramNames);
        $this->assertContains('providers', $paramNames);
        $this->assertContains('siteName', $paramNames);
        $this->assertContains('reportDate', $paramNames);
        $this->assertContains('joomlaVersion', $paramNames);
        $this->assertContains('criticalCount', $paramNames);
        $this->assertContains('warningCount', $paramNames);
        $this->assertContains('goodCount', $paramNames);
        $this->assertContains('totalCount', $paramNames);
        $this->assertContains('showMySitesGuruBanner', $paramNames);
        $this->assertContains('logoUrl', $paramNames);
    }

    public function testViewUsesModelForData(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'display');
        $source = file_get_contents($reflectionMethod->getFileName());

        $this->assertStringContainsString('getModel', $source);
    }

    public function testViewUsesRunChecksFromModel(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'display');
        $source = file_get_contents($reflectionMethod->getFileName());

        $this->assertStringContainsString('runChecks', $source);
    }

    public function testViewGetsResultsByCategory(): void
    {
        $reflectionMethod = new \ReflectionMethod(HtmlexportView::class, 'display');
        $source = file_get_contents($reflectionMethod->getFileName());

        $this->assertStringContainsString('getResultsByCategory', $source);
    }

    public function testViewUsesPluginHelper(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlexportView::class);
        $source = file_get_contents($reflectionClass->getFileName());

        $this->assertStringContainsString('PluginHelper', $source);
    }

    public function testViewUsesHealthStatusEnum(): void
    {
        $reflectionClass = new \ReflectionClass(HtmlexportView::class);
        $source = file_get_contents($reflectionClass->getFileName());

        $this->assertStringContainsString('HealthStatus', $source);
    }
}

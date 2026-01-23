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
use MySitesGuru\HealthChecker\Component\Administrator\View\Report\JsonView;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(JsonView::class)]
class JsonViewTest extends TestCase
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
        $jsonView = new JsonView();

        $this->assertInstanceOf(JsonView::class, $jsonView);
    }

    public function testViewExtendsBaseJsonView(): void
    {
        $jsonView = new JsonView();

        $this->assertInstanceOf(\Joomla\CMS\MVC\View\JsonView::class, $jsonView);
    }

    public function testDisplayMethodExists(): void
    {
        $this->assertTrue(method_exists(JsonView::class, 'display'));
    }

    public function testDisplayMethodAcceptsNullTemplate(): void
    {
        $reflectionMethod = new \ReflectionMethod(JsonView::class, 'display');
        $parameters = $reflectionMethod->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('tpl', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->allowsNull());
    }

    public function testDisplayMethodReturnsVoid(): void
    {
        $reflectionMethod = new \ReflectionMethod(JsonView::class, 'display');
        $returnType = $reflectionMethod->getReturnType();

        $this->assertNotNull($returnType);
        $this->assertSame('void', $returnType->getName());
    }

    public function testViewHasCorrectNamespace(): void
    {
        $reflectionClass = new \ReflectionClass(JsonView::class);

        $this->assertSame(
            'MySitesGuru\HealthChecker\Component\Administrator\View\Report',
            $reflectionClass->getNamespaceName(),
        );
    }

    public function testViewIsNotAbstract(): void
    {
        $reflectionClass = new \ReflectionClass(JsonView::class);

        $this->assertFalse($reflectionClass->isAbstract());
    }

    public function testViewIsNotFinal(): void
    {
        $reflectionClass = new \ReflectionClass(JsonView::class);

        $this->assertFalse($reflectionClass->isFinal());
    }

    public function testViewClassName(): void
    {
        $reflectionClass = new \ReflectionClass(JsonView::class);

        $this->assertSame('JsonView', $reflectionClass->getShortName());
    }

    public function testViewUsesModelForData(): void
    {
        // The display method uses $this->getModel() to get data
        $reflectionMethod = new \ReflectionMethod(JsonView::class, 'display');
        $source = file_get_contents($reflectionMethod->getFileName());

        // Extract the display method body
        $this->assertStringContainsString('getModel', $source);
    }

    public function testViewUsesRunChecksFromModel(): void
    {
        $reflectionMethod = new \ReflectionMethod(JsonView::class, 'display');
        $source = file_get_contents($reflectionMethod->getFileName());

        $this->assertStringContainsString('runChecks', $source);
    }

    public function testViewUsesToJsonFromModel(): void
    {
        $reflectionMethod = new \ReflectionMethod(JsonView::class, 'display');
        $source = file_get_contents($reflectionMethod->getFileName());

        $this->assertStringContainsString('toJson', $source);
    }
}

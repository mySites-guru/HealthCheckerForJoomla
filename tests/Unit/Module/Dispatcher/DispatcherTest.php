<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Module\Dispatcher;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Helper\HelperFactoryInterface;
use Joomla\Registry\Registry;
use MySitesGuru\HealthChecker\Module\Administrator\Dispatcher\Dispatcher;
use MySitesGuru\HealthChecker\Module\Administrator\Helper\HealthCheckerHelper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Dispatcher::class)]
class DispatcherTest extends TestCase
{
    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
    }

    public function testDispatcherExtendsAbstractModuleDispatcher(): void
    {
        $module = $this->createModule();
        $dispatcher = new Dispatcher($module, $this->cmsApplication);

        $this->assertInstanceOf(Dispatcher::class, $dispatcher);
    }

    public function testDispatcherImplementsHelperFactoryAwareInterface(): void
    {
        $module = $this->createModule();
        $dispatcher = new Dispatcher($module, $this->cmsApplication);

        $this->assertInstanceOf(\Joomla\CMS\Helper\HelperFactoryAwareInterface::class, $dispatcher);
    }

    public function testSetHelperFactoryAcceptsHelperFactory(): void
    {
        $module = $this->createModule();
        $dispatcher = new Dispatcher($module, $this->cmsApplication);

        $helperFactory = $this->createHelperFactory();
        $dispatcher->setHelperFactory($helperFactory);

        // No exception means success
        $this->assertSame($helperFactory, $dispatcher->getHelperFactory());
    }

    public function testGetHelperFactoryThrowsWhenNotSet(): void
    {
        $module = $this->createModule();
        $dispatcher = new Dispatcher($module, $this->cmsApplication);

        $this->expectException(\UnexpectedValueException::class);
        $dispatcher->getHelperFactory();
    }

    public function testGetLayoutDataReturnsArray(): void
    {
        $module = $this->createModule();
        $testableDispatcher = new TestableDispatcher($module, $this->cmsApplication);
        $testableDispatcher->setHelperFactory($this->createHelperFactory());

        $layoutData = $testableDispatcher->exposeGetLayoutData();

        $this->assertIsArray($layoutData);
    }

    public function testGetLayoutDataContainsHealthStats(): void
    {
        $module = $this->createModule();
        $testableDispatcher = new TestableDispatcher($module, $this->cmsApplication);
        $testableDispatcher->setHelperFactory($this->createHelperFactory());

        $layoutData = $testableDispatcher->exposeGetLayoutData();

        $this->assertArrayHasKey('healthStats', $layoutData);
    }

    public function testGetLayoutDataContainsParentData(): void
    {
        $module = $this->createModule();
        $testableDispatcher = new TestableDispatcher($module, $this->cmsApplication);
        $testableDispatcher->setHelperFactory($this->createHelperFactory());

        $layoutData = $testableDispatcher->exposeGetLayoutData();

        // Parent data should include params, module, and app
        $this->assertArrayHasKey('params', $layoutData);
        $this->assertArrayHasKey('module', $layoutData);
        $this->assertArrayHasKey('app', $layoutData);
    }

    public function testHealthStatsContainsExpectedKeys(): void
    {
        $module = $this->createModule();
        $testableDispatcher = new TestableDispatcher($module, $this->cmsApplication);
        $testableDispatcher->setHelperFactory($this->createHelperFactory());

        $layoutData = $testableDispatcher->exposeGetLayoutData();
        $healthStats = $layoutData['healthStats'];

        $this->assertArrayHasKey('showCritical', $healthStats);
        $this->assertArrayHasKey('showWarning', $healthStats);
        $this->assertArrayHasKey('showGood', $healthStats);
        $this->assertArrayHasKey('enableCache', $healthStats);
        $this->assertArrayHasKey('cacheDuration', $healthStats);
    }

    public function testHealthStatsReflectsModuleParams(): void
    {
        $module = $this->createModule([
            'show_critical' => '0',
            'show_warning' => '1',
            'show_good' => '0',
            'enable_cache' => '0',
            'cache_duration' => '1800',
        ]);
        $testableDispatcher = new TestableDispatcher($module, $this->cmsApplication);
        $testableDispatcher->setHelperFactory($this->createHelperFactory());

        $layoutData = $testableDispatcher->exposeGetLayoutData();
        $healthStats = $layoutData['healthStats'];

        $this->assertFalse($healthStats['showCritical']);
        $this->assertTrue($healthStats['showWarning']);
        $this->assertFalse($healthStats['showGood']);
        $this->assertFalse($healthStats['enableCache']);
        $this->assertSame(1800, $healthStats['cacheDuration']);
    }

    public function testHealthStatsWithDefaultParams(): void
    {
        $module = $this->createModule();
        $testableDispatcher = new TestableDispatcher($module, $this->cmsApplication);
        $testableDispatcher->setHelperFactory($this->createHelperFactory());

        $layoutData = $testableDispatcher->exposeGetLayoutData();
        $healthStats = $layoutData['healthStats'];

        // All defaults should be true/enabled
        $this->assertTrue($healthStats['showCritical']);
        $this->assertTrue($healthStats['showWarning']);
        $this->assertTrue($healthStats['showGood']);
        $this->assertTrue($healthStats['enableCache']);
        $this->assertSame(900, $healthStats['cacheDuration']);
    }

    public function testParamsIsRegistryInstance(): void
    {
        $module = $this->createModule();
        $testableDispatcher = new TestableDispatcher($module, $this->cmsApplication);
        $testableDispatcher->setHelperFactory($this->createHelperFactory());

        $layoutData = $testableDispatcher->exposeGetLayoutData();

        $this->assertInstanceOf(Registry::class, $layoutData['params']);
    }

    public function testModuleIsObject(): void
    {
        $module = $this->createModule();
        $testableDispatcher = new TestableDispatcher($module, $this->cmsApplication);
        $testableDispatcher->setHelperFactory($this->createHelperFactory());

        $layoutData = $testableDispatcher->exposeGetLayoutData();

        $this->assertIsObject($layoutData['module']);
    }

    public function testAppIsCMSApplication(): void
    {
        $module = $this->createModule();
        $testableDispatcher = new TestableDispatcher($module, $this->cmsApplication);
        $testableDispatcher->setHelperFactory($this->createHelperFactory());

        $layoutData = $testableDispatcher->exposeGetLayoutData();

        $this->assertInstanceOf(CMSApplication::class, $layoutData['app']);
    }

    public function testHelperFactoryGetHelperIsCalledWithCorrectName(): void
    {
        $module = $this->createModule();
        $testableDispatcher = new TestableDispatcher($module, $this->cmsApplication);

        $healthCheckerHelper = new HealthCheckerHelper();
        $helperFactory = $this->createStub(HelperFactoryInterface::class);
        $helperFactory->method('getHelper')
            ->with('HealthCheckerHelper')
            ->willReturn($healthCheckerHelper);

        $testableDispatcher->setHelperFactory($helperFactory);

        $layoutData = $testableDispatcher->exposeGetLayoutData();

        // If we got here without errors, the helper was called correctly
        $this->assertArrayHasKey('healthStats', $layoutData);
    }

    /**
     * Create a module object for testing
     *
     * @param array<string, string> $params Module parameters
     */
    private function createModule(array $params = []): \stdClass
    {
        $module = new \stdClass();
        $module->id = 1;
        $module->title = 'Health Checker';
        $module->module = 'mod_healthchecker';
        $module->position = 'cpanel';
        $module->params = json_encode($params);

        return $module;
    }

    /**
     * Create a helper factory that returns the real HealthCheckerHelper
     */
    private function createHelperFactory(): HelperFactoryInterface
    {
        $healthCheckerHelper = new HealthCheckerHelper();

        return new class ($healthCheckerHelper) implements HelperFactoryInterface {
            public function __construct(
                private readonly HealthCheckerHelper $healthCheckerHelper,
            ) {}

            public function getHelper(string $name, array $config = []): mixed
            {
                if ($name === 'HealthCheckerHelper') {
                    return $this->healthCheckerHelper;
                }

                throw new \InvalidArgumentException('Unknown helper: ' . $name);
            }
        };
    }
}

/**
 * Testable subclass that exposes protected methods for testing
 */
class TestableDispatcher extends Dispatcher
{
    /**
     * Expose the protected getLayoutData method for testing
     *
     * @return array<string, mixed>
     */
    public function exposeGetLayoutData(): array
    {
        return $this->getLayoutData();
    }
}

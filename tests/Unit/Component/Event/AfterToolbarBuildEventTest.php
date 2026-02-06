<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Event;

use Joomla\CMS\Toolbar\Toolbar;
use MySitesGuru\HealthChecker\Component\Administrator\Event\AfterToolbarBuildEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AfterToolbarBuildEvent::class)]
class AfterToolbarBuildEventTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear any cached toolbar instances
        Toolbar::clearInstances();
    }

    public function testEventConstruction(): void
    {
        $toolbar = Toolbar::getInstance('toolbar');
        $afterToolbarBuildEvent = new AfterToolbarBuildEvent($toolbar);

        $this->assertInstanceOf(AfterToolbarBuildEvent::class, $afterToolbarBuildEvent);
    }

    public function testEventHasCorrectName(): void
    {
        $toolbar = Toolbar::getInstance('toolbar');
        $afterToolbarBuildEvent = new AfterToolbarBuildEvent($toolbar);

        $this->assertSame(HealthCheckerEvents::AFTER_TOOLBAR_BUILD->value, $afterToolbarBuildEvent->getName());
    }

    public function testEventNameMatchesExpectedValue(): void
    {
        $toolbar = Toolbar::getInstance('toolbar');
        $afterToolbarBuildEvent = new AfterToolbarBuildEvent($toolbar);

        $this->assertSame('onHealthCheckerAfterToolbarBuild', $afterToolbarBuildEvent->getName());
    }

    public function testGetToolbarReturnsSameInstance(): void
    {
        $toolbar = Toolbar::getInstance('toolbar');
        $afterToolbarBuildEvent = new AfterToolbarBuildEvent($toolbar);

        $this->assertSame($toolbar, $afterToolbarBuildEvent->getToolbar());
    }

    public function testGetToolbarReturnsToolbarInstance(): void
    {
        $toolbar = Toolbar::getInstance('toolbar');
        $afterToolbarBuildEvent = new AfterToolbarBuildEvent($toolbar);

        $this->assertInstanceOf(Toolbar::class, $afterToolbarBuildEvent->getToolbar());
    }

    public function testToolbarCanBeModifiedThroughEvent(): void
    {
        $toolbar = Toolbar::getInstance('toolbar');
        $afterToolbarBuildEvent = new AfterToolbarBuildEvent($toolbar);

        // Add a button via the event's toolbar
        $afterToolbarBuildEvent->getToolbar()
            ->standardButton('test', 'Test Button');

        // Verify button was added
        $buttons = $toolbar->getButtons();
        $this->assertCount(1, $buttons);
    }

    public function testToolbarCanAddMultipleButtonsThroughEvent(): void
    {
        $toolbar = Toolbar::getInstance('toolbar');
        $afterToolbarBuildEvent = new AfterToolbarBuildEvent($toolbar);

        $eventToolbar = $afterToolbarBuildEvent->getToolbar();
        $eventToolbar->standardButton('button1', 'Button 1');
        $eventToolbar->standardButton('button2', 'Button 2');
        $eventToolbar->linkButton('link1')
            ->text('Link Button');

        $buttons = $toolbar->getButtons();
        $this->assertCount(3, $buttons);
    }

    public function testEventExtendsJoomlaEvent(): void
    {
        $toolbar = Toolbar::getInstance('toolbar');
        $afterToolbarBuildEvent = new AfterToolbarBuildEvent($toolbar);

        $this->assertInstanceOf(\Joomla\Event\Event::class, $afterToolbarBuildEvent);
    }

    public function testEventIsFinal(): void
    {
        $reflectionClass = new \ReflectionClass(AfterToolbarBuildEvent::class);

        $this->assertTrue($reflectionClass->isFinal());
    }

    public function testToolbarPropertyIsReadonly(): void
    {
        $reflectionClass = new \ReflectionClass(AfterToolbarBuildEvent::class);
        $constructor = $reflectionClass->getConstructor();

        $this->assertNotNull($constructor);

        $parameters = $constructor->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertSame('toolbar', $parameters[0]->getName());
    }

    public function testMultipleEventInstancesWithDifferentToolbars(): void
    {
        $toolbar1 = Toolbar::getInstance('toolbar1');
        $toolbar2 = Toolbar::getInstance('toolbar2');

        $event1 = new AfterToolbarBuildEvent($toolbar1);
        $event2 = new AfterToolbarBuildEvent($toolbar2);

        $this->assertNotSame($event1->getToolbar(), $event2->getToolbar());
        $this->assertSame($toolbar1, $event1->getToolbar());
        $this->assertSame($toolbar2, $event2->getToolbar());
    }
}

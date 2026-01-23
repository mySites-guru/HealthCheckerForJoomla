<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Event;

use MySitesGuru\HealthChecker\Component\Administrator\Event\BeforeReportDisplayEvent;
use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BeforeReportDisplayEvent::class)]
class BeforeReportDisplayEventTest extends TestCase
{
    public function testEventHasCorrectName(): void
    {
        $event = new BeforeReportDisplayEvent();
        $this->assertSame(HealthCheckerEvents::BEFORE_REPORT_DISPLAY->value, $event->getName());
    }

    public function testGetHtmlContentReturnsEmptyStringByDefault(): void
    {
        $event = new BeforeReportDisplayEvent();
        $this->assertSame('', $event->getHtmlContent());
    }

    public function testAddHtmlContentAddsContent(): void
    {
        $event = new BeforeReportDisplayEvent();
        $event->addHtmlContent('<div>Test content</div>');

        $this->assertSame('<div>Test content</div>', $event->getHtmlContent());
    }

    public function testAddHtmlContentConcatenatesMultipleContents(): void
    {
        $event = new BeforeReportDisplayEvent();
        $event->addHtmlContent('<div>First</div>');
        $event->addHtmlContent('<div>Second</div>');
        $event->addHtmlContent('<div>Third</div>');

        $expected = "<div>First</div>\n<div>Second</div>\n<div>Third</div>";
        $this->assertSame($expected, $event->getHtmlContent());
    }

    public function testAddHtmlContentPreservesOrder(): void
    {
        $event = new BeforeReportDisplayEvent();
        $event->addHtmlContent('A');
        $event->addHtmlContent('B');
        $event->addHtmlContent('C');

        $this->assertStringContainsString('A', $event->getHtmlContent());
        $this->assertStringContainsString('B', $event->getHtmlContent());
        $this->assertStringContainsString('C', $event->getHtmlContent());

        // Verify order
        $content = $event->getHtmlContent();
        $posA = strpos($content, 'A');
        $posB = strpos($content, 'B');
        $posC = strpos($content, 'C');

        $this->assertLessThan($posB, $posA);
        $this->assertLessThan($posC, $posB);
    }

    public function testAddHtmlContentAcceptsEmptyString(): void
    {
        $event = new BeforeReportDisplayEvent();
        $event->addHtmlContent('');

        $this->assertSame('', $event->getHtmlContent());
    }

    public function testAddHtmlContentAcceptsComplexHtml(): void
    {
        $event = new BeforeReportDisplayEvent();
        $html = '<div class="alert alert-info" data-id="123">
            <h4>Notice</h4>
            <p>Some <strong>important</strong> information.</p>
        </div>';

        $event->addHtmlContent($html);

        $this->assertSame($html, $event->getHtmlContent());
    }

    public function testEventExtendsJoomlaEvent(): void
    {
        $event = new BeforeReportDisplayEvent();
        $this->assertInstanceOf(\Joomla\Event\Event::class, $event);
    }
}

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
        $beforeReportDisplayEvent = new BeforeReportDisplayEvent();
        $this->assertSame(HealthCheckerEvents::BEFORE_REPORT_DISPLAY->value, $beforeReportDisplayEvent->getName());
    }

    public function testGetHtmlContentReturnsEmptyStringByDefault(): void
    {
        $beforeReportDisplayEvent = new BeforeReportDisplayEvent();
        $this->assertSame('', $beforeReportDisplayEvent->getHtmlContent());
    }

    public function testAddHtmlContentAddsContent(): void
    {
        $beforeReportDisplayEvent = new BeforeReportDisplayEvent();
        $beforeReportDisplayEvent->addHtmlContent('<div>Test content</div>');

        $this->assertSame('<div>Test content</div>', $beforeReportDisplayEvent->getHtmlContent());
    }

    public function testAddHtmlContentConcatenatesMultipleContents(): void
    {
        $beforeReportDisplayEvent = new BeforeReportDisplayEvent();
        $beforeReportDisplayEvent->addHtmlContent('<div>First</div>');
        $beforeReportDisplayEvent->addHtmlContent('<div>Second</div>');
        $beforeReportDisplayEvent->addHtmlContent('<div>Third</div>');

        $expected = "<div>First</div>\n<div>Second</div>\n<div>Third</div>";
        $this->assertSame($expected, $beforeReportDisplayEvent->getHtmlContent());
    }

    public function testAddHtmlContentPreservesOrder(): void
    {
        $beforeReportDisplayEvent = new BeforeReportDisplayEvent();
        $beforeReportDisplayEvent->addHtmlContent('A');
        $beforeReportDisplayEvent->addHtmlContent('B');
        $beforeReportDisplayEvent->addHtmlContent('C');

        $this->assertStringContainsString('A', $beforeReportDisplayEvent->getHtmlContent());
        $this->assertStringContainsString('B', $beforeReportDisplayEvent->getHtmlContent());
        $this->assertStringContainsString('C', $beforeReportDisplayEvent->getHtmlContent());

        // Verify order
        $content = $beforeReportDisplayEvent->getHtmlContent();
        $posA = strpos($content, 'A');
        $posB = strpos($content, 'B');
        $posC = strpos($content, 'C');

        $this->assertLessThan($posB, $posA);
        $this->assertLessThan($posC, $posB);
    }

    public function testAddHtmlContentAcceptsEmptyString(): void
    {
        $beforeReportDisplayEvent = new BeforeReportDisplayEvent();
        $beforeReportDisplayEvent->addHtmlContent('');

        $this->assertSame('', $beforeReportDisplayEvent->getHtmlContent());
    }

    public function testAddHtmlContentAcceptsComplexHtml(): void
    {
        $beforeReportDisplayEvent = new BeforeReportDisplayEvent();
        $html = '<div class="alert alert-info" data-id="123">
            <h4>Notice</h4>
            <p>Some <strong>important</strong> information.</p>
        </div>';

        $beforeReportDisplayEvent->addHtmlContent($html);

        $this->assertSame($html, $beforeReportDisplayEvent->getHtmlContent());
    }

    public function testEventExtendsJoomlaEvent(): void
    {
        $beforeReportDisplayEvent = new BeforeReportDisplayEvent();
        $this->assertInstanceOf(\Joomla\Event\Event::class, $beforeReportDisplayEvent);
    }
}

<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Event;

use MySitesGuru\HealthChecker\Component\Administrator\Event\HealthCheckerEvents;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HealthCheckerEvents::class)]
class HealthCheckerEventsTest extends TestCase
{
    public function testCollectProvidersEventValue(): void
    {
        $this->assertSame('onHealthCheckerCollectProviders', HealthCheckerEvents::COLLECT_PROVIDERS->value);
    }

    public function testCollectCategoriesEventValue(): void
    {
        $this->assertSame('onHealthCheckerCollectCategories', HealthCheckerEvents::COLLECT_CATEGORIES->value);
    }

    public function testCollectChecksEventValue(): void
    {
        $this->assertSame('onHealthCheckerCollectChecks', HealthCheckerEvents::COLLECT_CHECKS->value);
    }

    public function testBeforeReportDisplayEventValue(): void
    {
        $this->assertSame('onHealthCheckerBeforeReportDisplay', HealthCheckerEvents::BEFORE_REPORT_DISPLAY->value);
    }

    public function testEnumHasCorrectNumberOfCases(): void
    {
        $cases = HealthCheckerEvents::cases();
        $this->assertCount(5, $cases);
    }

    public function testAfterToolbarBuildEventValue(): void
    {
        $this->assertSame('onHealthCheckerAfterToolbarBuild', HealthCheckerEvents::AFTER_TOOLBAR_BUILD->value);
    }

    public function testGetHandlerMethodForAfterToolbarBuild(): void
    {
        $this->assertSame('onAfterToolbarBuild', HealthCheckerEvents::AFTER_TOOLBAR_BUILD->getHandlerMethod());
    }

    public function testAllEventNamesStartWithOnHealthChecker(): void
    {
        foreach (HealthCheckerEvents::cases() as $case) {
            $this->assertStringStartsWith('onHealthChecker', $case->value);
        }
    }

    public function testEventNamesAreUnique(): void
    {
        $values = array_map(fn($case) => $case->value, HealthCheckerEvents::cases());
        $uniqueValues = array_unique($values);

        $this->assertCount(count($values), $uniqueValues);
    }

    public function testEventEnumIsBacked(): void
    {
        $reflection = new \ReflectionEnum(HealthCheckerEvents::class);
        $this->assertTrue($reflection->isBacked());
    }

    public function testEventEnumBackingTypeIsString(): void
    {
        $reflection = new \ReflectionEnum(HealthCheckerEvents::class);
        $this->assertSame('string', $reflection->getBackingType()?->getName());
    }

    public function testGetHandlerMethodForCollectProviders(): void
    {
        $this->assertSame('onCollectProviders', HealthCheckerEvents::COLLECT_PROVIDERS->getHandlerMethod());
    }

    public function testGetHandlerMethodForCollectCategories(): void
    {
        $this->assertSame('onCollectCategories', HealthCheckerEvents::COLLECT_CATEGORIES->getHandlerMethod());
    }

    public function testGetHandlerMethodForCollectChecks(): void
    {
        $this->assertSame('onCollectChecks', HealthCheckerEvents::COLLECT_CHECKS->getHandlerMethod());
    }

    public function testGetHandlerMethodForBeforeReportDisplay(): void
    {
        $this->assertSame('onBeforeReportDisplay', HealthCheckerEvents::BEFORE_REPORT_DISPLAY->getHandlerMethod());
    }
}

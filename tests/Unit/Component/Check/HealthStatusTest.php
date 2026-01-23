<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Component\Check;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(HealthStatus::class)]
class HealthStatusTest extends TestCase
{
    public function testCriticalCaseExists(): void
    {
        $status = HealthStatus::Critical;
        $this->assertInstanceOf(HealthStatus::class, $status);
        $this->assertSame('critical', $status->value);
    }

    public function testWarningCaseExists(): void
    {
        $status = HealthStatus::Warning;
        $this->assertInstanceOf(HealthStatus::class, $status);
        $this->assertSame('warning', $status->value);
    }

    public function testGoodCaseExists(): void
    {
        $status = HealthStatus::Good;
        $this->assertInstanceOf(HealthStatus::class, $status);
        $this->assertSame('good', $status->value);
    }

    public function testGetLabelReturnsCriticalLanguageKey(): void
    {
        $status = HealthStatus::Critical;
        $this->assertSame('COM_HEALTHCHECKER_STATUS_CRITICAL', $status->getLabel());
    }

    public function testGetLabelReturnsWarningLanguageKey(): void
    {
        $status = HealthStatus::Warning;
        $this->assertSame('COM_HEALTHCHECKER_STATUS_WARNING', $status->getLabel());
    }

    public function testGetLabelReturnsGoodLanguageKey(): void
    {
        $status = HealthStatus::Good;
        $this->assertSame('COM_HEALTHCHECKER_STATUS_GOOD', $status->getLabel());
    }

    public function testGetIconReturnsCriticalIcon(): void
    {
        $status = HealthStatus::Critical;
        $this->assertSame('fa-times-circle', $status->getIcon());
    }

    public function testGetIconReturnsWarningIcon(): void
    {
        $status = HealthStatus::Warning;
        $this->assertSame('fa-exclamation-triangle', $status->getIcon());
    }

    public function testGetIconReturnsGoodIcon(): void
    {
        $status = HealthStatus::Good;
        $this->assertSame('fa-check-circle', $status->getIcon());
    }

    public function testGetBadgeClassReturnsCriticalClass(): void
    {
        $status = HealthStatus::Critical;
        $this->assertSame('bg-danger', $status->getBadgeClass());
    }

    public function testGetBadgeClassReturnsWarningClass(): void
    {
        $status = HealthStatus::Warning;
        $this->assertSame('bg-warning text-dark', $status->getBadgeClass());
    }

    public function testGetBadgeClassReturnsGoodClass(): void
    {
        $status = HealthStatus::Good;
        $this->assertSame('bg-success', $status->getBadgeClass());
    }

    public function testGetSortOrderReturnsCriticalPriority(): void
    {
        $status = HealthStatus::Critical;
        $this->assertSame(1, $status->getSortOrder());
    }

    public function testGetSortOrderReturnsWarningPriority(): void
    {
        $status = HealthStatus::Warning;
        $this->assertSame(2, $status->getSortOrder());
    }

    public function testGetSortOrderReturnsGoodPriority(): void
    {
        $status = HealthStatus::Good;
        $this->assertSame(3, $status->getSortOrder());
    }

    public function testSortOrderPriorityIsCorrect(): void
    {
        $this->assertLessThan(
            HealthStatus::Warning->getSortOrder(),
            HealthStatus::Critical->getSortOrder(),
            'Critical should have higher priority (lower number) than Warning',
        );

        $this->assertLessThan(
            HealthStatus::Good->getSortOrder(),
            HealthStatus::Warning->getSortOrder(),
            'Warning should have higher priority (lower number) than Good',
        );
    }

    #[DataProvider('statusValueProvider')]
    public function testEnumBackedByString(HealthStatus $status, string $expectedValue): void
    {
        $this->assertSame($expectedValue, $status->value);
    }

    /**
     * Data provider for status values
     *
     * @return array<string, array{HealthStatus, string}>
     */
    public static function statusValueProvider(): array
    {
        return [
            'critical' => [HealthStatus::Critical, 'critical'],
            'warning' => [HealthStatus::Warning, 'warning'],
            'good' => [HealthStatus::Good, 'good'],
        ];
    }

    public function testCanCreateFromString(): void
    {
        $critical = HealthStatus::from('critical');
        $warning = HealthStatus::from('warning');
        $good = HealthStatus::from('good');

        $this->assertSame(HealthStatus::Critical, $critical);
        $this->assertSame(HealthStatus::Warning, $warning);
        $this->assertSame(HealthStatus::Good, $good);
    }
}

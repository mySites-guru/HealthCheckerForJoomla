<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\MaxInputTimeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MaxInputTimeCheck::class)]
class MaxInputTimeCheckTest extends TestCase
{
    private MaxInputTimeCheck $maxInputTimeCheck;

    protected function setUp(): void
    {
        $this->maxInputTimeCheck = new MaxInputTimeCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.max_input_time', $this->maxInputTimeCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->maxInputTimeCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->maxInputTimeCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->maxInputTimeCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->maxInputTimeCheck->run();

        $this->assertSame('system.max_input_time', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $healthCheckResult = $this->maxInputTimeCheck->run();

        // Result depends on PHP configuration - never returns Critical
        $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testNeverReturnsCritical(): void
    {
        // This check never returns Critical status according to source code
        $healthCheckResult = $this->maxInputTimeCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->maxInputTimeCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->maxInputTimeCheck->run();
        $result2 = $this->maxInputTimeCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testDescriptionIncludesCurrentValue(): void
    {
        $currentValue = (int) ini_get('max_input_time');
        $healthCheckResult = $this->maxInputTimeCheck->run();

        // If not unlimited, description should include the current value
        if ($currentValue !== -1 && $currentValue !== 0) {
            $this->assertStringContainsString((string) $currentValue, $healthCheckResult->description);
        } else {
            $this->assertStringContainsString('unlimited', $healthCheckResult->description);
        }
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->maxInputTimeCheck->run();

        $this->assertSame('system.max_input_time', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }
}

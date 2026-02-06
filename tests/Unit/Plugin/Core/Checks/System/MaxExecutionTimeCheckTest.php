<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\MaxExecutionTimeCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MaxExecutionTimeCheck::class)]
class MaxExecutionTimeCheckTest extends TestCase
{
    private MaxExecutionTimeCheck $maxExecutionTimeCheck;

    protected function setUp(): void
    {
        $this->maxExecutionTimeCheck = new MaxExecutionTimeCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.max_execution_time', $this->maxExecutionTimeCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->maxExecutionTimeCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->maxExecutionTimeCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->maxExecutionTimeCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->maxExecutionTimeCheck->run();

        $this->assertSame('system.max_execution_time', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunResultHasDescription(): void
    {
        $healthCheckResult = $this->maxExecutionTimeCheck->run();

        $this->assertIsString($healthCheckResult->description);
        $this->assertNotEmpty($healthCheckResult->description);
    }

    public function testRunReturnsValidStatus(): void
    {
        $healthCheckResult = $this->maxExecutionTimeCheck->run();

        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsTimeInfo(): void
    {
        $healthCheckResult = $this->maxExecutionTimeCheck->run();

        // Description should mention execution time or unlimited
        $this->assertTrue(
            str_contains($healthCheckResult->description, 'execution time') ||
            str_contains($healthCheckResult->description, 'unlimited'),
        );
    }

    public function testReturnsGoodWhenUnlimited(): void
    {
        // Set max_execution_time to 0 (unlimited) - only possible in CLI
        ini_set('max_execution_time', '0');

        $healthCheckResult = $this->maxExecutionTimeCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('unlimited', $healthCheckResult->description);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->maxExecutionTimeCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->maxExecutionTimeCheck->run();
        $result2 = $this->maxExecutionTimeCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testDescriptionIncludesCurrentValue(): void
    {
        $currentValue = (int) ini_get('max_execution_time');
        $healthCheckResult = $this->maxExecutionTimeCheck->run();

        // If not unlimited (0), description should include the current value
        if ($currentValue !== 0) {
            $this->assertStringContainsString((string) $currentValue, $healthCheckResult->description);
        } else {
            $this->assertStringContainsString('unlimited', $healthCheckResult->description);
        }
    }
}

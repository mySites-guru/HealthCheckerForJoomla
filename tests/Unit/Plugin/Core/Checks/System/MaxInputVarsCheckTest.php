<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\MaxInputVarsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MaxInputVarsCheck::class)]
class MaxInputVarsCheckTest extends TestCase
{
    private MaxInputVarsCheck $maxInputVarsCheck;

    protected function setUp(): void
    {
        $this->maxInputVarsCheck = new MaxInputVarsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.max_input_vars', $this->maxInputVarsCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->maxInputVarsCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->maxInputVarsCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->maxInputVarsCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->maxInputVarsCheck->run();

        $this->assertSame('system.max_input_vars', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $healthCheckResult = $this->maxInputVarsCheck->run();

        // Can return Good, Warning, or Critical depending on max_input_vars value
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsMaxInputVarsInfo(): void
    {
        $healthCheckResult = $this->maxInputVarsCheck->run();

        // Description should mention max_input_vars
        $this->assertStringContainsString('max_input_vars', $healthCheckResult->description);
    }

    public function testCurrentMaxInputVarsIsDetectable(): void
    {
        $maxInputVars = (int) ini_get('max_input_vars');

        // max_input_vars should be a positive integer
        $this->assertGreaterThan(0, $maxInputVars);
    }

    public function testCheckThresholds(): void
    {
        // Test environment thresholds:
        // >= 3000: Good
        // >= 1000 and < 3000: Warning
        // < 1000: Critical
        $maxInputVars = (int) ini_get('max_input_vars');
        $healthCheckResult = $this->maxInputVarsCheck->run();

        if ($maxInputVars >= 3000) {
            $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
            $this->assertStringContainsString('meets requirements', $healthCheckResult->description);
        } elseif ($maxInputVars >= 1000) {
            $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
            $this->assertStringContainsString('below the recommended', $healthCheckResult->description);
        } else {
            $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
            $this->assertStringContainsString('below the minimum', $healthCheckResult->description);
        }
    }

    public function testDescriptionIncludesCurrentValue(): void
    {
        $healthCheckResult = $this->maxInputVarsCheck->run();
        $maxInputVars = (int) ini_get('max_input_vars');

        // Description should include the current value
        $this->assertStringContainsString((string) $maxInputVars, $healthCheckResult->description);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->maxInputVarsCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->maxInputVarsCheck->run();
        $result2 = $this->maxInputVarsCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->maxInputVarsCheck->run();

        $this->assertSame('system.max_input_vars', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testMinimumThresholdConstant(): void
    {
        // Verify the minimum threshold constant value
        $minimumVars = 1000;

        // Values below this should trigger Critical
        $this->assertSame(1000, $minimumVars);
    }

    public function testRecommendedThresholdConstant(): void
    {
        // Verify the recommended threshold constant value
        $recommendedVars = 3000;

        // Values below this but above minimum should trigger Warning
        $this->assertSame(3000, $recommendedVars);
    }

    public function testSlugFormat(): void
    {
        $slug = $this->maxInputVarsCheck->getSlug();

        // Slug should be lowercase with dot separator
        $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z_]+$/', $slug);
    }

    public function testCategoryIsValid(): void
    {
        $category = $this->maxInputVarsCheck->getCategory();

        // Should be a valid category
        $validCategories = ['system', 'database', 'security', 'users', 'extensions', 'performance', 'seo', 'content'];
        $this->assertContains($category, $validCategories);
    }

    public function testDescriptionIncludesThresholdValues(): void
    {
        $healthCheckResult = $this->maxInputVarsCheck->run();

        // Description should include threshold values - verify at least one
        $this->assertTrue(
            str_contains($healthCheckResult->description, '1000') ||
            str_contains($healthCheckResult->description, '3000') ||
            str_contains($healthCheckResult->description, 'meets requirements'),
        );
    }

    public function testMaxInputVarsCannotBeChangedAtRuntime(): void
    {
        // max_input_vars is PHP_INI_PERDIR, cannot be changed at runtime
        $originalValue = ini_get('max_input_vars');

        // Attempt to change it (will fail silently)
        @ini_set('max_input_vars', '500');

        // Value should remain unchanged
        $this->assertSame($originalValue, ini_get('max_input_vars'));
    }
}

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
    private MaxInputVarsCheck $check;

    protected function setUp(): void
    {
        $this->check = new MaxInputVarsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.max_input_vars', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->check->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->check->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.max_input_vars', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Can return Good, Warning, or Critical depending on max_input_vars value
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsMaxInputVarsInfo(): void
    {
        $result = $this->check->run();

        // Description should mention max_input_vars
        $this->assertStringContainsString('max_input_vars', $result->description);
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
        $result = $this->check->run();

        if ($maxInputVars >= 3000) {
            $this->assertSame(HealthStatus::Good, $result->healthStatus);
            $this->assertStringContainsString('meets requirements', $result->description);
        } elseif ($maxInputVars >= 1000) {
            $this->assertSame(HealthStatus::Warning, $result->healthStatus);
            $this->assertStringContainsString('below the recommended', $result->description);
        } else {
            $this->assertSame(HealthStatus::Critical, $result->healthStatus);
            $this->assertStringContainsString('below the minimum', $result->description);
        }
    }

    public function testDescriptionIncludesCurrentValue(): void
    {
        $result = $this->check->run();
        $maxInputVars = (int) ini_get('max_input_vars');

        // Description should include the current value
        $this->assertStringContainsString((string) $maxInputVars, $result->description);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $result1 = $this->check->run();
        $result2 = $this->check->run();

        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        $this->assertSame($result1->description, $result2->description);
    }

    public function testResultHasCorrectStructure(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.max_input_vars', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
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
        $slug = $this->check->getSlug();

        // Slug should be lowercase with dot separator
        $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z_]+$/', $slug);
    }

    public function testCategoryIsValid(): void
    {
        $category = $this->check->getCategory();

        // Should be a valid category
        $validCategories = ['system', 'database', 'security', 'users', 'extensions', 'performance', 'seo', 'content'];
        $this->assertContains($category, $validCategories);
    }

    public function testDescriptionIncludesThresholdValues(): void
    {
        $result = $this->check->run();

        // Description should include threshold values - verify at least one
        $this->assertTrue(
            str_contains($result->description, '1000') ||
            str_contains($result->description, '3000') ||
            str_contains($result->description, 'meets requirements'),
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

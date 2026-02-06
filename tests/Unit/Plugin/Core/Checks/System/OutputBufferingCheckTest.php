<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\OutputBufferingCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OutputBufferingCheck::class)]
class OutputBufferingCheckTest extends TestCase
{
    private OutputBufferingCheck $outputBufferingCheck;

    protected function setUp(): void
    {
        $this->outputBufferingCheck = new OutputBufferingCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.output_buffering', $this->outputBufferingCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->outputBufferingCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->outputBufferingCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->outputBufferingCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->outputBufferingCheck->run();

        $this->assertSame('system.output_buffering', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunAlwaysReturnsGood(): void
    {
        // This check is informational and always returns Good
        $healthCheckResult = $this->outputBufferingCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunDescriptionContainsOutputBufferingInfo(): void
    {
        $healthCheckResult = $this->outputBufferingCheck->run();

        // Description should mention output buffering
        $this->assertStringContainsString('Output buffering', $healthCheckResult->description);
    }

    public function testCurrentOutputBufferingIsDetectable(): void
    {
        $outputBuffering = ini_get('output_buffering');

        // output_buffering should return a value (even if empty/false)
        $this->assertIsString($outputBuffering);
    }

    public function testDescriptionReflectsCurrentSetting(): void
    {
        $outputBuffering = ini_get('output_buffering');
        $healthCheckResult = $this->outputBufferingCheck->run();

        // Check that description reflects the actual setting
        if (in_array($outputBuffering, ['', '0', 'Off'], true)) {
            $this->assertStringContainsString('disabled', $healthCheckResult->description);
        } elseif ($outputBuffering === '1' || $outputBuffering === 'On') {
            $this->assertStringContainsString('enabled', $healthCheckResult->description);
        } else {
            // Numeric buffer size
            $this->assertStringContainsString('bytes', $healthCheckResult->description);
        }
    }

    public function testCheckIsInformationalOnly(): void
    {
        // This check should never return Warning or Critical
        $healthCheckResult = $this->outputBufferingCheck->run();

        $this->assertNotSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->outputBufferingCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->outputBufferingCheck->run();
        $result2 = $this->outputBufferingCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->outputBufferingCheck->run();

        $this->assertSame('system.output_buffering', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testSlugFormat(): void
    {
        $slug = $this->outputBufferingCheck->getSlug();

        // Slug should be lowercase with dot separator
        $this->assertMatchesRegularExpression('/^[a-z]+\.[a-z_]+$/', $slug);
    }

    public function testCategoryIsValid(): void
    {
        $category = $this->outputBufferingCheck->getCategory();

        // Should be a valid category
        $validCategories = ['system', 'database', 'security', 'users', 'extensions', 'performance', 'seo', 'content'];
        $this->assertContains($category, $validCategories);
    }

    public function testOutputBufferingIniGetReturnsString(): void
    {
        $value = ini_get('output_buffering');

        $this->assertIsString($value);
    }

    public function testOutputBufferingValueParsing(): void
    {
        $outputBuffering = ini_get('output_buffering');
        $healthCheckResult = $this->outputBufferingCheck->run();

        // Verify the output buffering value is reflected in description
        $this->assertStringContainsString('Output buffering', $healthCheckResult->description);

        if (in_array($outputBuffering, ['', '0', 'Off'], true)) {
            $this->assertStringContainsString('disabled', $healthCheckResult->description);
        } elseif ($outputBuffering === '1' || $outputBuffering === 'On') {
            $this->assertStringContainsString('enabled', $healthCheckResult->description);
        } elseif (is_numeric($outputBuffering) && (int) $outputBuffering > 1) {
            // Numeric buffer size
            $this->assertStringContainsString('bytes', $healthCheckResult->description);
            $this->assertStringContainsString($outputBuffering, $healthCheckResult->description);
        }
    }

    public function testDisabledOutputBufferingMentionsRecommended(): void
    {
        $outputBuffering = ini_get('output_buffering');
        $healthCheckResult = $this->outputBufferingCheck->run();

        if (in_array($outputBuffering, ['', '0', 'Off'], true)) {
            $this->assertStringContainsString('recommended', $healthCheckResult->description);
        }
    }
}

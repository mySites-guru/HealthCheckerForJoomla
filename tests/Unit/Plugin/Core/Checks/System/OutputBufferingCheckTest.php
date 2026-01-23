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
    private OutputBufferingCheck $check;

    protected function setUp(): void
    {
        $this->check = new OutputBufferingCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.output_buffering', $this->check->getSlug());
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

        $this->assertSame('system.output_buffering', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunAlwaysReturnsGood(): void
    {
        // This check is informational and always returns Good
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunDescriptionContainsOutputBufferingInfo(): void
    {
        $result = $this->check->run();

        // Description should mention output buffering
        $this->assertStringContainsString('Output buffering', $result->description);
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
        $result = $this->check->run();

        // Check that description reflects the actual setting
        if (in_array($outputBuffering, ['', '0', 'Off'], true)) {
            $this->assertStringContainsString('disabled', $result->description);
        } elseif ($outputBuffering === '1' || $outputBuffering === 'On') {
            $this->assertStringContainsString('enabled', $result->description);
        } else {
            // Numeric buffer size
            $this->assertStringContainsString('bytes', $result->description);
        }
    }

    public function testCheckIsInformationalOnly(): void
    {
        // This check should never return Warning or Critical
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
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

        $this->assertSame('system.output_buffering', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
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

    public function testOutputBufferingIniGetReturnsString(): void
    {
        $value = ini_get('output_buffering');

        $this->assertIsString($value);
    }

    public function testOutputBufferingValueParsing(): void
    {
        $outputBuffering = ini_get('output_buffering');
        $result = $this->check->run();

        // Verify the output buffering value is reflected in description
        $this->assertStringContainsString('Output buffering', $result->description);

        if (in_array($outputBuffering, ['', '0', 'Off'], true)) {
            $this->assertStringContainsString('disabled', $result->description);
        } elseif ($outputBuffering === '1' || $outputBuffering === 'On') {
            $this->assertStringContainsString('enabled', $result->description);
        } elseif (is_numeric($outputBuffering) && (int) $outputBuffering > 1) {
            // Numeric buffer size
            $this->assertStringContainsString('bytes', $result->description);
            $this->assertStringContainsString($outputBuffering, $result->description);
        }
    }

    public function testDisabledOutputBufferingMentionsRecommended(): void
    {
        $outputBuffering = ini_get('output_buffering');
        $result = $this->check->run();

        if (in_array($outputBuffering, ['', '0', 'Off'], true)) {
            $this->assertStringContainsString('recommended', $result->description);
        }
    }
}

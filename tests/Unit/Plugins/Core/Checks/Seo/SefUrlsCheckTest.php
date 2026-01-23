<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugins\Core\Checks\Seo;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Seo\SefUrlsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SefUrlsCheck::class)]
class SefUrlsCheckTest extends TestCase
{
    public function testGetSlugReturnsCorrectValue(): void
    {
        $check = new SefUrlsCheck();
        $this->assertSame('seo.sef_urls', $check->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $check = new SefUrlsCheck();
        $this->assertSame('seo', $check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $check = new SefUrlsCheck();
        $this->assertSame('core', $check->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $check = new SefUrlsCheck();
        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame('seo.sef_urls', $result->slug);
        $this->assertSame('seo', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsGoodOrWarningStatus(): void
    {
        $check = new SefUrlsCheck();
        $result = $check->run();

        // SEF URLs check only returns Good or Warning, never Critical
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning],
            'SEF URLs check should return Good or Warning status',
        );
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $check = new SefUrlsCheck();
        $result = $check->run();

        $this->assertNotEmpty($result->description);
    }

    public function testResultDescriptionMentionsSefOrUrls(): void
    {
        $check = new SefUrlsCheck();
        $result = $check->run();

        // Description mentions either "SEF" or "URLs" (Search Engine Friendly URLs)
        $this->assertTrue(
            str_contains(strtolower($result->description), 'sef')
            || str_contains(strtolower($result->description), 'url'),
            'Description should mention SEF or URLs',
        );
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $check = new SefUrlsCheck();

        // Database should be null (not injected)
        $this->assertNull($check->getDatabase());

        // Check should still work without database
        $result = $check->run();
        $this->assertInstanceOf(HealthCheckResult::class, $result);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $check = new SefUrlsCheck();

        $result1 = $check->run();
        $result2 = $check->run();

        // Results should be the same since SEF config doesn't change during test
        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        $this->assertSame($result1->description, $result2->description);
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $check = new SefUrlsCheck();
        $result = $check->run();

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('provider', $array);
    }
}

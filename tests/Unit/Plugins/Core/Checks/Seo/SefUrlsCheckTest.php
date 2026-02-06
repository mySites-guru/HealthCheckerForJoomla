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
        $sefUrlsCheck = new SefUrlsCheck();
        $this->assertSame('seo.sef_urls', $sefUrlsCheck->getSlug());
    }

    public function testGetCategoryReturnsSeo(): void
    {
        $sefUrlsCheck = new SefUrlsCheck();
        $this->assertSame('seo', $sefUrlsCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $sefUrlsCheck = new SefUrlsCheck();
        $this->assertSame('core', $sefUrlsCheck->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $sefUrlsCheck = new SefUrlsCheck();
        $healthCheckResult = $sefUrlsCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame('seo.sef_urls', $healthCheckResult->slug);
        $this->assertSame('seo', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsGoodOrWarningStatus(): void
    {
        $sefUrlsCheck = new SefUrlsCheck();
        $healthCheckResult = $sefUrlsCheck->run();

        // SEF URLs check only returns Good or Warning, never Critical
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning],
            'SEF URLs check should return Good or Warning status',
        );
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $sefUrlsCheck = new SefUrlsCheck();
        $healthCheckResult = $sefUrlsCheck->run();

        $this->assertNotEmpty($healthCheckResult->description);
    }

    public function testResultDescriptionMentionsSefOrUrls(): void
    {
        $sefUrlsCheck = new SefUrlsCheck();
        $healthCheckResult = $sefUrlsCheck->run();

        // Description mentions either "SEF" or "URLs" (Search Engine Friendly URLs)
        $this->assertTrue(
            str_contains(strtolower($healthCheckResult->description), 'sef')
            || str_contains(strtolower($healthCheckResult->description), 'url'),
            'Description should mention SEF or URLs',
        );
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $sefUrlsCheck = new SefUrlsCheck();

        // Database should be null (not injected)
        $this->assertNull($sefUrlsCheck->getDatabase());

        // Check should still work without database
        $healthCheckResult = $sefUrlsCheck->run();
        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $sefUrlsCheck = new SefUrlsCheck();

        $healthCheckResult = $sefUrlsCheck->run();
        $result2 = $sefUrlsCheck->run();

        // Results should be the same since SEF config doesn't change during test
        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $sefUrlsCheck = new SefUrlsCheck();
        $healthCheckResult = $sefUrlsCheck->run();

        $array = $healthCheckResult->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('slug', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('description', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertArrayHasKey('category', $array);
        $this->assertArrayHasKey('provider', $array);
    }
}

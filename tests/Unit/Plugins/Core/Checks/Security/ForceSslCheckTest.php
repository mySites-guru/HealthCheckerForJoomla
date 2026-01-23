<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugins\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthCheckResult;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\ForceSslCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ForceSslCheck::class)]
class ForceSslCheckTest extends TestCase
{
    public function testGetSlugReturnsCorrectValue(): void
    {
        $check = new ForceSslCheck();
        $this->assertSame('security.force_ssl', $check->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $check = new ForceSslCheck();
        $this->assertSame('security', $check->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $check = new ForceSslCheck();
        $this->assertSame('core', $check->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $check = new ForceSslCheck();
        $result = $check->run();

        $this->assertInstanceOf(HealthCheckResult::class, $result);
        $this->assertSame('security.force_ssl', $result->slug);
        $this->assertSame('security', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $check = new ForceSslCheck();
        $result = $check->run();

        // Force SSL check can return Good, Warning, or Critical
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
            'Force SSL check should return a valid status',
        );
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $check = new ForceSslCheck();
        $result = $check->run();

        $this->assertNotEmpty($result->description);
    }

    public function testResultDescriptionMentionsSsl(): void
    {
        $check = new ForceSslCheck();
        $result = $check->run();

        $this->assertStringContainsStringIgnoringCase('ssl', $result->description);
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $check = new ForceSslCheck();

        // Database should be null (not injected)
        $this->assertNull($check->getDatabase());

        // Check should still work without database
        $result = $check->run();
        $this->assertInstanceOf(HealthCheckResult::class, $result);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $check = new ForceSslCheck();

        $result1 = $check->run();
        $result2 = $check->run();

        // Results should be the same since SSL config doesn't change during test
        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        $this->assertSame($result1->description, $result2->description);
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $check = new ForceSslCheck();
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

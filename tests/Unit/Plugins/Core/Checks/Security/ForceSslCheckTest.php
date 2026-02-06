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
        $forceSslCheck = new ForceSslCheck();
        $this->assertSame('security.force_ssl', $forceSslCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $forceSslCheck = new ForceSslCheck();
        $this->assertSame('security', $forceSslCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $forceSslCheck = new ForceSslCheck();
        $this->assertSame('core', $forceSslCheck->getProvider());
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $forceSslCheck = new ForceSslCheck();
        $healthCheckResult = $forceSslCheck->run();

        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
        $this->assertSame('security.force_ssl', $healthCheckResult->slug);
        $this->assertSame('security', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $forceSslCheck = new ForceSslCheck();
        $healthCheckResult = $forceSslCheck->run();

        // Force SSL check can return Good, Warning, or Critical
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
            'Force SSL check should return a valid status',
        );
    }

    public function testResultDescriptionIsNotEmpty(): void
    {
        $forceSslCheck = new ForceSslCheck();
        $healthCheckResult = $forceSslCheck->run();

        $this->assertNotEmpty($healthCheckResult->description);
    }

    public function testResultDescriptionMentionsSsl(): void
    {
        $forceSslCheck = new ForceSslCheck();
        $healthCheckResult = $forceSslCheck->run();

        $this->assertStringContainsStringIgnoringCase('ssl', $healthCheckResult->description);
    }

    public function testCheckDoesNotRequireDatabase(): void
    {
        $forceSslCheck = new ForceSslCheck();

        // Database should be null (not injected)
        $this->assertNull($forceSslCheck->getDatabase());

        // Check should still work without database
        $healthCheckResult = $forceSslCheck->run();
        $this->assertInstanceOf(HealthCheckResult::class, $healthCheckResult);
    }

    public function testCheckIsConsistentOnMultipleRuns(): void
    {
        $forceSslCheck = new ForceSslCheck();

        $healthCheckResult = $forceSslCheck->run();
        $result2 = $forceSslCheck->run();

        // Results should be the same since SSL config doesn't change during test
        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testResultCanBeConvertedToArray(): void
    {
        $forceSslCheck = new ForceSslCheck();
        $healthCheckResult = $forceSslCheck->run();

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

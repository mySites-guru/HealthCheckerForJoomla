<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\CorsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CorsCheck::class)]
class CorsCheckTest extends TestCase
{
    private CorsCheck $corsCheck;

    protected function setUp(): void
    {
        $this->corsCheck = new CorsCheck();
    }

    protected function tearDown(): void
    {
        // Reset Factory application
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.cors', $this->corsCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->corsCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->corsCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->corsCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsGoodWhenCorsDisabled(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', false);
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenCorsEnabledWithWildcard(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', true);
        $cmsApplication->set('cors_allow_origin', '*');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('wildcard', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenCorsEnabledWithRestrictedOrigin(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', true);
        $cmsApplication->set('cors_allow_origin', 'https://example.com');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('restricted', $healthCheckResult->description);
        $this->assertStringContainsString('example.com', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenCorsEnabledAsString1(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', '1');
        $cmsApplication->set('cors_allow_origin', '*');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenCorsEnabledAsInteger1(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', 1);
        $cmsApplication->set('cors_allow_origin', '*');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenCorsDisabledWithString0(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', '0');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenCorsDisabledWithIntegerZero(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', 0);
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenCorsNotSet(): void
    {
        $cmsApplication = new CMSApplication();
        // Don't set cors, should default to false
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunResultContainsSlug(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', false);
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame('security.cors', $healthCheckResult->slug);
    }

    public function testRunResultContainsTitle(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', false);
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testRunResultHasProvider(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', false);
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunResultHasCategory(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', false);
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame('security', $healthCheckResult->category);
    }

    public function testRunNeverReturnsCritical(): void
    {
        // Per the docblock, this check does not return critical
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', true);
        $cmsApplication->set('cors_allow_origin', '*');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertNotSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenCorsEnabledWithHttpsDomain(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', true);
        $cmsApplication->set('cors_allow_origin', 'https://api.example.com');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('api.example.com', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenCorsEnabledWithMultipleDomains(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', true);
        $cmsApplication->set('cors_allow_origin', 'https://example.com,https://app.example.com');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenCorsEnabledWithDefaultWildcard(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', true);
        // Don't set cors_allow_origin, should default to '*'
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testWarningDescriptionMentionsSecurity(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', true);
        $cmsApplication->set('cors_allow_origin', '*');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('security', strtolower($healthCheckResult->description));
    }

    public function testWarningDescriptionMentionsTrustedDomains(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', true);
        $cmsApplication->set('cors_allow_origin', '*');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('trusted domains', $healthCheckResult->description);
    }

    public function testGoodDescriptionMentionsCrossOrigin(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', false);
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertTrue(
            stripos($healthCheckResult->description, 'cross-origin') !== false ||
            stripos($healthCheckResult->description, 'Cross-origin') !== false,
        );
    }

    public function testRunResultDescriptionIsNotEmpty(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', false);
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertNotEmpty($healthCheckResult->description);
    }

    public function testRunReturnsValidStatus(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', true);
        $cmsApplication->set('cors_allow_origin', '*');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testRunWithCorsEnabledAsNull(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', null);
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        // null is not in [true, '1', 1] so should be treated as disabled
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithCorsEnabledAsEmptyString(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', '');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        // Empty string is not in [true, '1', 1] so should be treated as disabled
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testGoodWithRestrictedOriginShowsOriginInDescription(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('cors', true);
        $cmsApplication->set('cors_allow_origin', 'https://myapp.example.com');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->corsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('myapp.example.com', $healthCheckResult->description);
    }
}

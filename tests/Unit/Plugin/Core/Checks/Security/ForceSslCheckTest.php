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
use Joomla\CMS\Uri\Uri;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\ForceSslCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ForceSslCheck::class)]
class ForceSslCheckTest extends TestCase
{
    private ForceSslCheck $forceSslCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        Factory::setApplication($this->cmsApplication);
        Uri::resetMockSsl();
        $this->forceSslCheck = new ForceSslCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
        Uri::resetMockSsl();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.force_ssl', $this->forceSslCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->forceSslCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->forceSslCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->forceSslCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithForceSslEntireSiteReturnsGood(): void
    {
        $this->cmsApplication->set('force_ssl', 2);

        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('entire site', $healthCheckResult->description);
    }

    public function testRunWithForceSslAdministratorOnlyReturnsWarning(): void
    {
        $this->cmsApplication->set('force_ssl', 1);

        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Administrator only', $healthCheckResult->description);
    }

    public function testRunWithForceSslDisabledAndNoHttpsReturnsCritical(): void
    {
        $this->cmsApplication->set('force_ssl', 0);
        // Uri::getInstance()->isSsl() returns false by default in stubs

        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('SSL', $healthCheckResult->description);
    }

    public function testRunWithForceSslNotSetAndNoHttpsReturnsCritical(): void
    {
        // Don't set force_ssl, default is -1 (not set in Joomla 5)
        // Uri::getInstance()->isSsl() returns false by default

        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('disabled', strtolower($healthCheckResult->description));
    }

    public function testRunWithForceSslMinusOneAndNoHttpsReturnsCritical(): void
    {
        $this->cmsApplication->set('force_ssl', -1);
        // Uri::getInstance()->isSsl() returns false by default

        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Enable SSL', $healthCheckResult->description);
    }

    public function testRunReturnsValidStatus(): void
    {
        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunResultContainsSlug(): void
    {
        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame('security.force_ssl', $healthCheckResult->slug);
    }

    public function testRunResultContainsTitle(): void
    {
        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testRunResultHasProvider(): void
    {
        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunResultHasCategory(): void
    {
        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame('security', $healthCheckResult->category);
    }

    public function testRunWithForceSslUnexpectedValueReturnsGood(): void
    {
        // Test fallback case for unexpected values (e.g., 3 or higher)
        $this->cmsApplication->set('force_ssl', 99);

        $healthCheckResult = $this->forceSslCheck->run();

        // Should hit the fallback case
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('appears correct', $healthCheckResult->description);
    }

    public function testRunWithForceSslStringValueZeroReturnsCritical(): void
    {
        // Test that string '0' is cast to int correctly
        $this->cmsApplication->set('force_ssl', '0');

        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testRunWithForceSslStringValueOneReturnsWarning(): void
    {
        // Test that string '1' is cast to int correctly
        $this->cmsApplication->set('force_ssl', '1');

        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithForceSslStringValueTwoReturnsGood(): void
    {
        // Test that string '2' is cast to int correctly
        $this->cmsApplication->set('force_ssl', '2');

        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testCriticalDescriptionMentionsSecurity(): void
    {
        $this->cmsApplication->set('force_ssl', 0);

        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('security', strtolower($healthCheckResult->description));
    }

    public function testWarningForAdminOnlyMentionsOption2(): void
    {
        $this->cmsApplication->set('force_ssl', 1);

        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('option 2', $healthCheckResult->description);
    }

    public function testRunWithForceSslDisabledButHttpsReturnsWarning(): void
    {
        // Force SSL disabled but currently using HTTPS
        $this->cmsApplication->set('force_ssl', 0);
        Uri::setMockSsl(true);

        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('HTTPS', $healthCheckResult->description);
        $this->assertStringContainsString('Force SSL is disabled', $healthCheckResult->description);
    }

    public function testRunWithForceSslMinusOneButHttpsReturnsWarning(): void
    {
        // Force SSL not set (-1) but currently using HTTPS
        $this->cmsApplication->set('force_ssl', -1);
        Uri::setMockSsl(true);

        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('HTTPS', $healthCheckResult->description);
        $this->assertStringContainsString('Force SSL is disabled', $healthCheckResult->description);
    }

    public function testWarningWhenHttpsButNoForceSslSuggestsEnabling(): void
    {
        // Using HTTPS but Force SSL is disabled - should suggest enabling
        $this->cmsApplication->set('force_ssl', 0);
        Uri::setMockSsl(true);

        $healthCheckResult = $this->forceSslCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('Enable Force SSL', $healthCheckResult->description);
    }
}

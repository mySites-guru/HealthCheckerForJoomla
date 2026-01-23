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
    private ForceSslCheck $check;

    private CMSApplication $app;

    protected function setUp(): void
    {
        $this->app = new CMSApplication();
        Factory::setApplication($this->app);
        Uri::resetMockSsl();
        $this->check = new ForceSslCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
        Uri::resetMockSsl();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.force_ssl', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->check->getCategory());
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

    public function testRunWithForceSslEntireSiteReturnsGood(): void
    {
        $this->app->set('force_ssl', 2);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('entire site', $result->description);
    }

    public function testRunWithForceSslAdministratorOnlyReturnsWarning(): void
    {
        $this->app->set('force_ssl', 1);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Administrator only', $result->description);
    }

    public function testRunWithForceSslDisabledAndNoHttpsReturnsCritical(): void
    {
        $this->app->set('force_ssl', 0);
        // Uri::getInstance()->isSsl() returns false by default in stubs

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('SSL', $result->description);
    }

    public function testRunWithForceSslNotSetAndNoHttpsReturnsCritical(): void
    {
        // Don't set force_ssl, default is -1 (not set in Joomla 5)
        // Uri::getInstance()->isSsl() returns false by default

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('disabled', strtolower($result->description));
    }

    public function testRunWithForceSslMinusOneAndNoHttpsReturnsCritical(): void
    {
        $this->app->set('force_ssl', -1);
        // Uri::getInstance()->isSsl() returns false by default

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('Enable SSL', $result->description);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunResultContainsSlug(): void
    {
        $result = $this->check->run();

        $this->assertSame('security.force_ssl', $result->slug);
    }

    public function testRunResultContainsTitle(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->title);
    }

    public function testRunResultHasProvider(): void
    {
        $result = $this->check->run();

        $this->assertSame('core', $result->provider);
    }

    public function testRunResultHasCategory(): void
    {
        $result = $this->check->run();

        $this->assertSame('security', $result->category);
    }

    public function testRunWithForceSslUnexpectedValueReturnsGood(): void
    {
        // Test fallback case for unexpected values (e.g., 3 or higher)
        $this->app->set('force_ssl', 99);

        $result = $this->check->run();

        // Should hit the fallback case
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('appears correct', $result->description);
    }

    public function testRunWithForceSslStringValueZeroReturnsCritical(): void
    {
        // Test that string '0' is cast to int correctly
        $this->app->set('force_ssl', '0');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testRunWithForceSslStringValueOneReturnsWarning(): void
    {
        // Test that string '1' is cast to int correctly
        $this->app->set('force_ssl', '1');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testRunWithForceSslStringValueTwoReturnsGood(): void
    {
        // Test that string '2' is cast to int correctly
        $this->app->set('force_ssl', '2');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testCriticalDescriptionMentionsSecurity(): void
    {
        $this->app->set('force_ssl', 0);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('security', strtolower($result->description));
    }

    public function testWarningForAdminOnlyMentionsOption2(): void
    {
        $this->app->set('force_ssl', 1);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('option 2', $result->description);
    }

    public function testRunWithForceSslDisabledButHttpsReturnsWarning(): void
    {
        // Force SSL disabled but currently using HTTPS
        $this->app->set('force_ssl', 0);
        Uri::setMockSsl(true);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('HTTPS', $result->description);
        $this->assertStringContainsString('Force SSL is disabled', $result->description);
    }

    public function testRunWithForceSslMinusOneButHttpsReturnsWarning(): void
    {
        // Force SSL not set (-1) but currently using HTTPS
        $this->app->set('force_ssl', -1);
        Uri::setMockSsl(true);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('HTTPS', $result->description);
        $this->assertStringContainsString('Force SSL is disabled', $result->description);
    }

    public function testWarningWhenHttpsButNoForceSslSuggestsEnabling(): void
    {
        // Using HTTPS but Force SSL is disabled - should suggest enabling
        $this->app->set('force_ssl', 0);
        Uri::setMockSsl(true);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Enable Force SSL', $result->description);
    }
}

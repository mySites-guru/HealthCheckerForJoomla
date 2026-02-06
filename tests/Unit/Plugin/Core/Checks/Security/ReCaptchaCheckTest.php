<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\ReCaptchaCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReCaptchaCheck::class)]
class ReCaptchaCheckTest extends TestCase
{
    private ReCaptchaCheck $reCaptchaCheck;

    protected function setUp(): void
    {
        $this->reCaptchaCheck = new ReCaptchaCheck();
    }

    protected function tearDown(): void
    {
        // Reset Factory application
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.recaptcha', $this->reCaptchaCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->reCaptchaCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->reCaptchaCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->reCaptchaCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->reCaptchaCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsWarningWhenNoCaptchaPluginsEnabled(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('captcha', 'recaptcha');
        Factory::setApplication($cmsApplication);

        // No captcha plugins enabled
        $database = MockDatabaseFactory::createWithResult(0);
        $this->reCaptchaCheck->setDatabase($database);

        $healthCheckResult = $this->reCaptchaCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No CAPTCHA plugins are enabled', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenCaptchaPluginEnabledButNotDefault(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('captcha', '0'); // No default captcha set
        Factory::setApplication($cmsApplication);

        // Captcha plugin is enabled
        $database = MockDatabaseFactory::createWithResult(1);
        $this->reCaptchaCheck->setDatabase($database);

        $healthCheckResult = $this->reCaptchaCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not set as default', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenCaptchaPluginEnabledButDefaultEmpty(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('captcha', ''); // Empty default captcha
        Factory::setApplication($cmsApplication);

        // Captcha plugin is enabled
        $database = MockDatabaseFactory::createWithResult(1);
        $this->reCaptchaCheck->setDatabase($database);

        $healthCheckResult = $this->reCaptchaCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('not set as default', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenCaptchaProperlyConfigured(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('captcha', 'recaptcha');
        Factory::setApplication($cmsApplication);

        // Captcha plugin is enabled
        $database = MockDatabaseFactory::createWithResult(1);
        $this->reCaptchaCheck->setDatabase($database);

        $healthCheckResult = $this->reCaptchaCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('configured for form protection', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWithMultipleCaptchaPluginsEnabled(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('captcha', 'hcaptcha');
        Factory::setApplication($cmsApplication);

        // Multiple captcha plugins are enabled
        $database = MockDatabaseFactory::createWithResult(3);
        $this->reCaptchaCheck->setDatabase($database);

        $healthCheckResult = $this->reCaptchaCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }
}

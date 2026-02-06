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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\MailerSecurityCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MailerSecurityCheck::class)]
class MailerSecurityCheckTest extends TestCase
{
    private MailerSecurityCheck $mailerSecurityCheck;

    protected function setUp(): void
    {
        $this->mailerSecurityCheck = new MailerSecurityCheck();
    }

    protected function tearDown(): void
    {
        // Reset Factory application
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.mailer_security', $this->mailerSecurityCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->mailerSecurityCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->mailerSecurityCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->mailerSecurityCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsGoodWhenUsingPhpMail(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('mailer', 'mail');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->mailerSecurityCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('PHP mail()', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenUsingSendmail(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('mailer', 'sendmail');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->mailerSecurityCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('sendmail', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenSmtpWithoutEncryption(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('mailer', 'smtp');
        $cmsApplication->set('smtpsecure', 'none');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->mailerSecurityCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('without encryption', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenSmtpWithEmptyEncryption(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('mailer', 'smtp');
        $cmsApplication->set('smtpsecure', '');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->mailerSecurityCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenSmtpWithTls(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('mailer', 'smtp');
        $cmsApplication->set('smtpsecure', 'tls');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->mailerSecurityCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('TLS', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenSmtpWithSsl(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('mailer', 'smtp');
        $cmsApplication->set('smtpsecure', 'ssl');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->mailerSecurityCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('SSL', $healthCheckResult->description);
    }

    public function testRunReturnsGoodForOtherMailerTypes(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('mailer', 'custom_mailer');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->mailerSecurityCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('custom_mailer', $healthCheckResult->description);
    }

    public function testRunWithDefaultMailerSetting(): void
    {
        $cmsApplication = new CMSApplication();
        // mailer default is 'mail' when not set
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->mailerSecurityCheck->run();

        // Default is 'mail' which returns Good
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }
}

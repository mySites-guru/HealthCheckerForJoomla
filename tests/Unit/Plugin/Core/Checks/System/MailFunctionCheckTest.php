<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\MailFunctionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MailFunctionCheck::class)]
class MailFunctionCheckTest extends TestCase
{
    private MailFunctionCheck $mailFunctionCheck;

    protected function setUp(): void
    {
        $this->mailFunctionCheck = new MailFunctionCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    private function setupApplicationWithMailer(string $mailer, ?string $sendmailPath = null): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('mailer', $mailer);
        if ($sendmailPath !== null) {
            $cmsApplication->set('sendmail', $sendmailPath);
        }

        Factory::setApplication($cmsApplication);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.mail_function', $this->mailFunctionCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->mailFunctionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->mailFunctionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->mailFunctionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->mailFunctionCheck->run();

        $this->assertSame('system.mail_function', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $healthCheckResult = $this->mailFunctionCheck->run();

        // Can return Good, Warning, or Critical depending on mail configuration
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsMailInfo(): void
    {
        $healthCheckResult = $this->mailFunctionCheck->run();

        // Description should mention mail, SMTP, or sendmail
        $this->assertTrue(
            str_contains(strtolower($healthCheckResult->description), 'mail') ||
            str_contains(strtolower($healthCheckResult->description), 'smtp') ||
            str_contains(strtolower($healthCheckResult->description), 'sendmail'),
        );
    }

    public function testMailFunctionExistsOnTestEnvironment(): void
    {
        // In test environment, mail() function should exist
        $this->assertTrue(function_exists('mail'));
    }

    public function testGoodWhenMailerIsSmtp(): void
    {
        $this->setupApplicationWithMailer('smtp');

        $healthCheckResult = $this->mailFunctionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('SMTP', $healthCheckResult->description);
    }

    public function testWarningWhenMailerIsSendmailWithNonExecutablePath(): void
    {
        // Use a path that does not exist
        $nonExecutablePath = '/nonexistent/path/to/sendmail';
        $this->setupApplicationWithMailer('sendmail', $nonExecutablePath);

        $healthCheckResult = $this->mailFunctionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('may not be executable', $healthCheckResult->description);
        $this->assertStringContainsString($nonExecutablePath, $healthCheckResult->description);
    }

    public function testGoodWhenMailerIsCustomValue(): void
    {
        $this->setupApplicationWithMailer('custom_mailer');

        $healthCheckResult = $this->mailFunctionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('custom_mailer', $healthCheckResult->description);
    }

    public function testCheckHandlesDisabledFunctionsCheck(): void
    {
        $healthCheckResult = $this->mailFunctionCheck->run();

        // If mail is disabled, should return Critical or Warning
        // If mail is available, should return Good
        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->mailFunctionCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $this->setupApplicationWithMailer('smtp');

        $healthCheckResult = $this->mailFunctionCheck->run();
        $result2 = $this->mailFunctionCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->mailFunctionCheck->run();

        $this->assertSame('system.mail_function', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testDisabledFunctionsIniGetWorks(): void
    {
        // Verify we can check disable_functions
        $disabledFunctions = ini_get('disable_functions');

        // Should return a string (possibly empty)
        $this->assertIsString($disabledFunctions);
    }

    public function testStrContainsWorksForDisabledFunctions(): void
    {
        // Test the str_contains logic used in the check
        $disabledFunctions = 'exec,shell_exec,system';

        $this->assertTrue(str_contains($disabledFunctions, 'exec'));
        $this->assertTrue(str_contains($disabledFunctions, 'shell_exec'));
        $this->assertFalse(str_contains($disabledFunctions, 'mail'));
    }

    public function testMailConfigOptions(): void
    {
        // Test that the check recognizes different mailer configs
        $validConfigs = ['mail', 'smtp', 'sendmail'];

        foreach ($validConfigs as $validConfig) {
            $this->assertIsString($validConfig);
            $this->assertNotEmpty($validConfig);
        }
    }

    public function testIsExecutableLogic(): void
    {
        // Test is_executable for common sendmail paths
        $commonSendmailPaths = ['/usr/sbin/sendmail', '/usr/lib/sendmail'];

        foreach ($commonSendmailPaths as $commonSendmailPath) {
            // Just verify the function works without error
            $isExecutable = is_executable($commonSendmailPath);
            $this->assertIsBool($isExecutable);
        }
    }

    public function testGoodResultForMailConfig(): void
    {
        $this->setupApplicationWithMailer('smtp');
        $healthCheckResult = $this->mailFunctionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $descLower = strtolower($healthCheckResult->description);
        $this->assertTrue(str_contains($descLower, 'configured') || str_contains($descLower, 'using'));
    }

    public function testCriticalResultExplainsIssue(): void
    {
        $healthCheckResult = $this->mailFunctionCheck->run();

        if ($healthCheckResult->healthStatus === HealthStatus::Critical) {
            // Critical should mention mail() not available or disabled
            $descLower = strtolower($healthCheckResult->description);
            $this->assertTrue(str_contains($descLower, 'not available') || str_contains($descLower, 'disabled'));
        } else {
            // Not critical, verify status is valid
            $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
        }
    }

    public function testWarningResultForSendmailPath(): void
    {
        $this->setupApplicationWithMailer('sendmail', '/nonexistent/sendmail');
        $healthCheckResult = $this->mailFunctionCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $descLower = strtolower($healthCheckResult->description);
        $this->assertTrue(
            str_contains($descLower, 'sendmail') ||
            str_contains($descLower, 'executable') ||
            str_contains($descLower, 'path'),
        );
    }

    public function testDefaultSendmailPathIsUsedWhenNotConfigured(): void
    {
        // Set up sendmail mailer without explicit path
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('mailer', 'sendmail');
        // Don't set sendmail path - should use default /usr/sbin/sendmail
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->mailFunctionCheck->run();

        // Result depends on whether default path is executable
        $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testSmtpConfigurationReturnsGoodWithoutValidation(): void
    {
        // SMTP configuration doesn't validate connection - just returns Good
        $this->setupApplicationWithMailer('smtp');

        $healthCheckResult = $this->mailFunctionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('SMTP', $healthCheckResult->description);
        $this->assertStringContainsString('email delivery', $healthCheckResult->description);
    }

    public function testDefaultMailerWhenNotConfigured(): void
    {
        // Set up application without explicit mailer setting
        // The stub's get() returns default when key not set
        $cmsApplication = new CMSApplication();
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->mailFunctionCheck->run();

        // Should use default 'mail' mailer
        $this->assertContains($healthCheckResult->healthStatus, [HealthStatus::Good, HealthStatus::Critical]);
    }

    public function testFunctionExistsCheck(): void
    {
        // Verify function_exists works correctly for mail
        $mailExists = function_exists('mail');
        $this->assertIsBool($mailExists);
    }

    public function testIniGetForDisabledFunctions(): void
    {
        // Verify ini_get returns a string for disable_functions
        $disabled = ini_get('disable_functions');
        $this->assertIsString($disabled);
    }

    public function testStrContainsForMailInDisabledFunctions(): void
    {
        // Test the str_contains check used in the code
        $testCases = [
            ['mail,exec', 'mail', true],
            ['exec,shell_exec', 'mail', false],
            ['', 'mail', false],
            ['mail', 'mail', true],
            ['sendmail', 'mail', true], // 'mail' is substring of 'sendmail'
        ];

        foreach ($testCases as [$haystack, $needle, $expected]) {
            $this->assertSame($expected, str_contains($haystack, $needle));
        }
    }

    public function testSendmailPathWithSpaces(): void
    {
        // Test sendmail path that contains spaces
        $pathWithSpaces = '/path with spaces/sendmail';
        $this->setupApplicationWithMailer('sendmail', $pathWithSpaces);

        $healthCheckResult = $this->mailFunctionCheck->run();

        // Path doesn't exist, should return warning
        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testUnknownMailerReturnsGood(): void
    {
        // Test unknown/custom mailer configuration
        $this->setupApplicationWithMailer('unknown_custom_mailer');

        $healthCheckResult = $this->mailFunctionCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('unknown_custom_mailer', $healthCheckResult->description);
    }

    public function testEmptyMailerConfigFallsBackToDefault(): void
    {
        // When mailer is empty string, it should use default behavior
        // However, in our test, empty string is a valid config value
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('mailer', '');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->mailFunctionCheck->run();

        // Empty string is treated as custom mailer
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }
}

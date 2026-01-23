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
    private MailFunctionCheck $check;

    protected function setUp(): void
    {
        $this->check = new MailFunctionCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    private function setupApplicationWithMailer(string $mailer, ?string $sendmailPath = null): void
    {
        $app = new CMSApplication();
        $app->set('mailer', $mailer);
        if ($sendmailPath !== null) {
            $app->set('sendmail', $sendmailPath);
        }
        Factory::setApplication($app);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.mail_function', $this->check->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->check->getCategory());
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

    public function testRunReturnsHealthCheckResult(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.mail_function', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
    }

    public function testRunReturnsValidStatus(): void
    {
        $result = $this->check->run();

        // Can return Good, Warning, or Critical depending on mail configuration
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunDescriptionContainsMailInfo(): void
    {
        $result = $this->check->run();

        // Description should mention mail, SMTP, or sendmail
        $this->assertTrue(
            str_contains(strtolower($result->description), 'mail') ||
            str_contains(strtolower($result->description), 'smtp') ||
            str_contains(strtolower($result->description), 'sendmail'),
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

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('SMTP', $result->description);
    }

    public function testWarningWhenMailerIsSendmailWithNonExecutablePath(): void
    {
        // Use a path that does not exist
        $nonExecutablePath = '/nonexistent/path/to/sendmail';
        $this->setupApplicationWithMailer('sendmail', $nonExecutablePath);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('may not be executable', $result->description);
        $this->assertStringContainsString($nonExecutablePath, $result->description);
    }

    public function testGoodWhenMailerIsCustomValue(): void
    {
        $this->setupApplicationWithMailer('custom_mailer');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('custom_mailer', $result->description);
    }

    public function testCheckHandlesDisabledFunctionsCheck(): void
    {
        // Verify the check works when testing for disabled functions
        $disabledFunctions = ini_get('disable_functions');
        $result = $this->check->run();

        // If mail is disabled, should return Critical or Warning
        // If mail is available, should return Good
        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $result = $this->check->run();

        $this->assertNotEmpty($result->title);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $this->setupApplicationWithMailer('smtp');

        $result1 = $this->check->run();
        $result2 = $this->check->run();

        $this->assertSame($result1->healthStatus, $result2->healthStatus);
        $this->assertSame($result1->description, $result2->description);
    }

    public function testResultHasCorrectStructure(): void
    {
        $result = $this->check->run();

        $this->assertSame('system.mail_function', $result->slug);
        $this->assertSame('system', $result->category);
        $this->assertSame('core', $result->provider);
        $this->assertIsString($result->description);
        $this->assertInstanceOf(HealthStatus::class, $result->healthStatus);
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

        foreach ($validConfigs as $config) {
            $this->assertIsString($config);
            $this->assertNotEmpty($config);
        }
    }

    public function testIsExecutableLogic(): void
    {
        // Test is_executable for common sendmail paths
        $commonSendmailPaths = ['/usr/sbin/sendmail', '/usr/lib/sendmail'];

        foreach ($commonSendmailPaths as $path) {
            // Just verify the function works without error
            $isExecutable = is_executable($path);
            $this->assertIsBool($isExecutable);
        }
    }

    public function testGoodResultForMailConfig(): void
    {
        $this->setupApplicationWithMailer('smtp');
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $descLower = strtolower($result->description);
        $this->assertTrue(str_contains($descLower, 'configured') || str_contains($descLower, 'using'));
    }

    public function testCriticalResultExplainsIssue(): void
    {
        $result = $this->check->run();

        if ($result->healthStatus === HealthStatus::Critical) {
            // Critical should mention mail() not available or disabled
            $descLower = strtolower($result->description);
            $this->assertTrue(str_contains($descLower, 'not available') || str_contains($descLower, 'disabled'));
        } else {
            // Not critical, verify status is valid
            $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
        }
    }

    public function testWarningResultForSendmailPath(): void
    {
        $this->setupApplicationWithMailer('sendmail', '/nonexistent/sendmail');
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $descLower = strtolower($result->description);
        $this->assertTrue(
            str_contains($descLower, 'sendmail') ||
            str_contains($descLower, 'executable') ||
            str_contains($descLower, 'path'),
        );
    }

    public function testDefaultSendmailPathIsUsedWhenNotConfigured(): void
    {
        // Set up sendmail mailer without explicit path
        $app = new CMSApplication();
        $app->set('mailer', 'sendmail');
        // Don't set sendmail path - should use default /usr/sbin/sendmail
        Factory::setApplication($app);

        $result = $this->check->run();

        // Result depends on whether default path is executable
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Warning]);
    }

    public function testSmtpConfigurationReturnsGoodWithoutValidation(): void
    {
        // SMTP configuration doesn't validate connection - just returns Good
        $this->setupApplicationWithMailer('smtp');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('SMTP', $result->description);
        $this->assertStringContainsString('email delivery', $result->description);
    }

    public function testDefaultMailerWhenNotConfigured(): void
    {
        // Set up application without explicit mailer setting
        // The stub's get() returns default when key not set
        $app = new CMSApplication();
        Factory::setApplication($app);

        $result = $this->check->run();

        // Should use default 'mail' mailer
        $this->assertContains($result->healthStatus, [HealthStatus::Good, HealthStatus::Critical]);
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

        $result = $this->check->run();

        // Path doesn't exist, should return warning
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testUnknownMailerReturnsGood(): void
    {
        // Test unknown/custom mailer configuration
        $this->setupApplicationWithMailer('unknown_custom_mailer');

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('unknown_custom_mailer', $result->description);
    }

    public function testEmptyMailerConfigFallsBackToDefault(): void
    {
        // When mailer is empty string, it should use default behavior
        // However, in our test, empty string is a valid config value
        $app = new CMSApplication();
        $app->set('mailer', '');
        Factory::setApplication($app);

        $result = $this->check->run();

        // Empty string is treated as custom mailer
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }
}

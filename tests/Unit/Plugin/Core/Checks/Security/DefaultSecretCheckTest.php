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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\DefaultSecretCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DefaultSecretCheck::class)]
class DefaultSecretCheckTest extends TestCase
{
    private DefaultSecretCheck $defaultSecretCheck;

    protected function setUp(): void
    {
        $this->defaultSecretCheck = new DefaultSecretCheck();
    }

    protected function tearDown(): void
    {
        // Reset Factory application
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.default_secret', $this->defaultSecretCheck->getSlug());
    }

    public function testGetCategoryReturnsSecurity(): void
    {
        $this->assertSame('security', $this->defaultSecretCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->defaultSecretCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->defaultSecretCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsCriticalWhenSecretIsEmpty(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', '');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('empty', $healthCheckResult->description);
    }

    public function testRunReturnsCriticalWhenSecretIsKnownDefault(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'FBVtggIk5lAXBMqz');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('default', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenSecretIsTooShort(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'abc123xyz'); // 9 characters, less than 16
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('shorter', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenSecretIs15Characters(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'abc123xyzABC012'); // exactly 15 characters
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenSecretIsUniqueAndLongEnough(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'myUniqueSecretKey123456'); // 22 characters, unique
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('unique', $healthCheckResult->description);
    }

    public function testRunReturnsGoodWhenSecretIsExactly16Characters(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'abc123xyzABC0123'); // exactly 16 characters
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsCriticalWhenSecretNotSet(): void
    {
        $cmsApplication = new CMSApplication();
        // Don't set secret, it will use the default empty string
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        // The default is empty string which triggers critical
        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testRunResultContainsSlug(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'myUniqueSecretKey123456');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame('security.default_secret', $healthCheckResult->slug);
    }

    public function testRunResultContainsTitle(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'myUniqueSecretKey123456');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testRunResultHasProvider(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'myUniqueSecretKey123456');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testRunResultHasCategory(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'myUniqueSecretKey123456');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame('security', $healthCheckResult->category);
    }

    public function testRunResultDescriptionIsNotEmpty(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'myUniqueSecretKey123456');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertNotEmpty($healthCheckResult->description);
    }

    public function testRunReturnsValidStatus(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'test');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertContains(
            $healthCheckResult->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunReturnsWarningWithSingleCharacter(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'a');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWithLongSecret(): void
    {
        $cmsApplication = new CMSApplication();
        // 64 character secret
        $cmsApplication->set('secret', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789ab');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testCriticalForEmptyMentionsCriticalSecurityIssue(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', '');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('critical', strtolower($healthCheckResult->description));
        $this->assertStringContainsString('security', strtolower($healthCheckResult->description));
    }

    public function testCriticalForDefaultMentionsGenerate(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'FBVtggIk5lAXBMqz');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertTrue(
            stripos($healthCheckResult->description, 'generate') !== false ||
            stripos($healthCheckResult->description, 'Generate') !== false,
        );
    }

    public function testWarningMentionsRecommended(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'shortkey'); // 8 chars
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('recommended', $healthCheckResult->description);
    }

    public function testGoodMentionsConfigured(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 'secureRandomKey12345');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('configured', $healthCheckResult->description);
    }

    public function testRunWithIntegerSecretValue(): void
    {
        // Edge case: secret stored as integer
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', 123456789012345678);
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        // strlen on integer is cast to string, so it's 18 chars
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithNumericStringSecret(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', '1234567890123456'); // 16 digit numeric string
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithSpecialCharactersInSecret(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', '!@#$%^&*()_+-=[]{}'); // 18 special characters
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithUnicodeSecret(): void
    {
        $cmsApplication = new CMSApplication();
        // Unicode secret - strlen will count bytes, not characters
        $cmsApplication->set('secret', 'SecretKey' . "\u{1F511}" . 'Test12345');
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        // The emoji takes 4 bytes, so total is 9 + 4 + 9 = 22 bytes
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithWhitespaceOnlySecret(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', '                '); // 16 spaces
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        // While technically 16 characters, empty() returns false for whitespace string
        // The check uses empty() first, which returns false for whitespace
        // So it passes the empty check and proceeds to length check
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithNullSecret(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', null);
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        // empty(null) returns true, so should be critical
        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
    }

    public function testBoundaryAt15Characters(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', '123456789012345'); // exactly 15 characters
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testBoundaryAt16Characters(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', '1234567890123456'); // exactly 16 characters
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testBoundaryAt17Characters(): void
    {
        $cmsApplication = new CMSApplication();
        $cmsApplication->set('secret', '12345678901234567'); // exactly 17 characters
        Factory::setApplication($cmsApplication);

        $healthCheckResult = $this->defaultSecretCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }
}

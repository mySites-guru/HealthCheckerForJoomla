<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Security;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Security\ConfigurationPhpPermissionsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationPhpPermissionsCheck::class)]
class ConfigurationPhpPermissionsCheckTest extends TestCase
{
    private ConfigurationPhpPermissionsCheck $check;

    private ?string $originalConfigPath = null;

    private ?string $tempConfigPath = null;

    protected function setUp(): void
    {
        $this->check = new ConfigurationPhpPermissionsCheck();

        // Ensure JPATH_ROOT exists for testing
        if (! is_dir(JPATH_ROOT)) {
            mkdir(JPATH_ROOT, 0755, true);
        }

        $this->originalConfigPath = JPATH_ROOT . '/configuration.php';
        $this->tempConfigPath = null;
    }

    protected function tearDown(): void
    {
        // Clean up any temp config file we created
        if ($this->tempConfigPath !== null && file_exists($this->tempConfigPath)) {
            chmod($this->tempConfigPath, 0644);
            unlink($this->tempConfigPath);
        }

        // Also clean up at JPATH_ROOT if exists
        if (file_exists($this->originalConfigPath)) {
            chmod($this->originalConfigPath, 0644);
            unlink($this->originalConfigPath);
        }
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('security.configuration_php_permissions', $this->check->getSlug());
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

    public function testRunReturnsValidStatus(): void
    {
        // Create a configuration.php with good permissions so we test a valid scenario
        $configPath = JPATH_ROOT . '/configuration.php';
        file_put_contents($configPath, '<?php // test config');
        chmod($configPath, 0640);

        $result = $this->check->run();

        $this->assertContains(
            $result->healthStatus,
            [HealthStatus::Good, HealthStatus::Warning, HealthStatus::Critical],
        );
    }

    public function testRunReturnsCriticalWhenConfigFileNotFound(): void
    {
        // Ensure config file does not exist
        $configPath = JPATH_ROOT . '/configuration.php';
        if (file_exists($configPath)) {
            unlink($configPath);
        }

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('not found', $result->description);
    }

    public function testRunReturnsGoodWhenPermissionsAre600(): void
    {
        $configPath = JPATH_ROOT . '/configuration.php';
        file_put_contents($configPath, '<?php // test config');
        chmod($configPath, 0600);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('600', $result->description);
        $this->assertStringContainsString('restrictive', $result->description);
    }

    public function testRunReturnsGoodWhenPermissionsAre640(): void
    {
        $configPath = JPATH_ROOT . '/configuration.php';
        file_put_contents($configPath, '<?php // test config');
        chmod($configPath, 0640);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
        $this->assertStringContainsString('640', $result->description);
        $this->assertStringContainsString('restrictive', $result->description);
    }

    public function testRunReturnsWarningWhenWorldReadable644(): void
    {
        $configPath = JPATH_ROOT . '/configuration.php';
        file_put_contents($configPath, '<?php // test config');
        chmod($configPath, 0644);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('644', $result->description);
        $this->assertStringContainsString('world-readable', $result->description);
    }

    public function testRunReturnsWarningWhenWorldReadable604(): void
    {
        $configPath = JPATH_ROOT . '/configuration.php';
        file_put_contents($configPath, '<?php // test config');
        chmod($configPath, 0604);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('604', $result->description);
        $this->assertStringContainsString('world-readable', $result->description);
    }

    public function testRunReturnsCriticalWhenWorldWritable666(): void
    {
        $configPath = JPATH_ROOT . '/configuration.php';
        file_put_contents($configPath, '<?php // test config');
        chmod($configPath, 0666);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('666', $result->description);
        $this->assertStringContainsString('world-writable', $result->description);
    }

    public function testRunReturnsCriticalWhenWorldWritable777(): void
    {
        $configPath = JPATH_ROOT . '/configuration.php';
        file_put_contents($configPath, '<?php // test config');
        chmod($configPath, 0777);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('777', $result->description);
        $this->assertStringContainsString('world-writable', $result->description);
    }

    public function testRunReturnsCriticalWhenWorldWritable662(): void
    {
        $configPath = JPATH_ROOT . '/configuration.php';
        file_put_contents($configPath, '<?php // test config');
        chmod($configPath, 0662);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('662', $result->description);
        $this->assertStringContainsString('world-writable', $result->description);
    }

    public function testRunReturnsCriticalWhenWorldWritable602(): void
    {
        $configPath = JPATH_ROOT . '/configuration.php';
        file_put_contents($configPath, '<?php // test config');
        chmod($configPath, 0602);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('602', $result->description);
        $this->assertStringContainsString('world-writable', $result->description);
    }

    public function testResultContainsSecurityCategory(): void
    {
        $configPath = JPATH_ROOT . '/configuration.php';
        file_put_contents($configPath, '<?php // test config');
        chmod($configPath, 0640);

        $result = $this->check->run();

        $this->assertSame('security', $result->category);
    }

    public function testResultContainsCorrectSlug(): void
    {
        $configPath = JPATH_ROOT . '/configuration.php';
        file_put_contents($configPath, '<?php // test config');
        chmod($configPath, 0640);

        $result = $this->check->run();

        $this->assertSame('security.configuration_php_permissions', $result->slug);
    }

    public function testResultContainsProvider(): void
    {
        $configPath = JPATH_ROOT . '/configuration.php';
        file_put_contents($configPath, '<?php // test config');
        chmod($configPath, 0640);

        $result = $this->check->run();

        $this->assertSame('core', $result->provider);
    }

    public function testCriticalDescriptionMentionsSecurityRisk(): void
    {
        $configPath = JPATH_ROOT . '/configuration.php';
        file_put_contents($configPath, '<?php // test config');
        chmod($configPath, 0777);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Critical, $result->healthStatus);
        $this->assertStringContainsString('critical security risk', strtolower($result->description));
    }

    public function testWarningDescriptionSuggestsRestrictingPermissions(): void
    {
        $configPath = JPATH_ROOT . '/configuration.php';
        file_put_contents($configPath, '<?php // test config');
        chmod($configPath, 0644);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('640', $result->description);
        $this->assertStringContainsString('600', $result->description);
    }
}

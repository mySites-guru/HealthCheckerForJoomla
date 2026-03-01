<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Extensions;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\DiscoverCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DiscoverCheck::class)]
class DiscoverCheckTest extends TestCase
{
    private DiscoverCheck $discoverCheck;

    private CMSApplication $cmsApplication;

    protected function setUp(): void
    {
        $this->cmsApplication = new CMSApplication();
        $this->cmsApplication->setComponent('com_installer', $this->createInstallerComponent());
        Factory::setApplication($this->cmsApplication);

        $this->discoverCheck = new DiscoverCheck();
    }

    protected function tearDown(): void
    {
        Factory::setApplication(null);
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.discover', $this->discoverCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->discoverCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->discoverCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->discoverCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->discoverCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithZeroDiscoveredExtensionsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithResult(0);
        $this->discoverCheck->setDatabase($database);

        $healthCheckResult = $this->discoverCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('DISCOVER_GOOD', $healthCheckResult->description);
    }

    public function testRunWithDiscoveredExtensionsReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(3);
        $this->discoverCheck->setDatabase($database);

        $healthCheckResult = $this->discoverCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('DISCOVER_WARNING', $healthCheckResult->description);
    }

    public function testRunWithOneDiscoveredExtensionReturnsWarning(): void
    {
        $database = MockDatabaseFactory::createWithResult(1);
        $this->discoverCheck->setDatabase($database);

        $healthCheckResult = $this->discoverCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithFailedDiscoverScanStillWorks(): void
    {
        $this->cmsApplication->setComponent('com_installer', null);
        $database = MockDatabaseFactory::createWithResult(0);
        $this->discoverCheck->setDatabase($database);

        $healthCheckResult = $this->discoverCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testGetActionUrlReturnsDiscoverPageOnWarning(): void
    {
        $this->assertSame(
            '/administrator/index.php?option=com_installer&view=discover',
            $this->discoverCheck->getActionUrl(HealthStatus::Warning),
        );
    }

    public function testGetActionUrlReturnsNullOnGood(): void
    {
        $this->assertNull($this->discoverCheck->getActionUrl(HealthStatus::Good));
    }

    public function testGetDocsUrlReturnsString(): void
    {
        $this->assertIsString($this->discoverCheck->getDocsUrl());
        $this->assertNotEmpty($this->discoverCheck->getDocsUrl());
    }

    /**
     * Create a mock com_installer component with a discover model.
     */
    private function createInstallerComponent(): object
    {
        return new class {
            public function getMVCFactory(): object
            {
                return new class {
                    public function createModel(string $name, string $prefix = '', array $config = []): object
                    {
                        return new class {
                            public function discover(): int
                            {
                                return 0;
                            }
                        };
                    }
                };
            }
        };
    }
}

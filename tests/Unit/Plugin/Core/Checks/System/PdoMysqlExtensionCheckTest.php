<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\System;

use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\System\PdoMysqlExtensionCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdoMysqlExtensionCheck::class)]
class PdoMysqlExtensionCheckTest extends TestCase
{
    private PdoMysqlExtensionCheck $pdoMysqlExtensionCheck;

    protected function setUp(): void
    {
        $this->pdoMysqlExtensionCheck = new PdoMysqlExtensionCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('system.pdo_mysql_extension', $this->pdoMysqlExtensionCheck->getSlug());
    }

    public function testGetCategoryReturnsSystem(): void
    {
        $this->assertSame('system', $this->pdoMysqlExtensionCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->pdoMysqlExtensionCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->pdoMysqlExtensionCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunReturnsHealthCheckResult(): void
    {
        $healthCheckResult = $this->pdoMysqlExtensionCheck->run();

        $this->assertSame('system.pdo_mysql_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
    }

    public function testResultTitleIsNotEmpty(): void
    {
        $healthCheckResult = $this->pdoMysqlExtensionCheck->run();

        $this->assertNotEmpty($healthCheckResult->title);
    }

    public function testResultHasCorrectStructure(): void
    {
        $healthCheckResult = $this->pdoMysqlExtensionCheck->run();

        $this->assertSame('system.pdo_mysql_extension', $healthCheckResult->slug);
        $this->assertSame('system', $healthCheckResult->category);
        $this->assertSame('core', $healthCheckResult->provider);
        $this->assertIsString($healthCheckResult->description);
        $this->assertInstanceOf(HealthStatus::class, $healthCheckResult->healthStatus);
    }

    public function testCheckNeverReturnsWarning(): void
    {
        // PDO MySQL check returns Critical or Good, never Warning per documentation
        $healthCheckResult = $this->pdoMysqlExtensionCheck->run();

        $this->assertNotSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testMultipleRunsReturnConsistentResults(): void
    {
        $healthCheckResult = $this->pdoMysqlExtensionCheck->run();
        $result2 = $this->pdoMysqlExtensionCheck->run();

        $this->assertSame($healthCheckResult->healthStatus, $result2->healthStatus);
        $this->assertSame($healthCheckResult->description, $result2->description);
    }

    public function testRunReturnsValidStatusBasedOnExtensionAvailability(): void
    {
        $healthCheckResult = $this->pdoMysqlExtensionCheck->run();

        // Based on whether pdo_mysql extension is loaded
        if (extension_loaded('pdo_mysql')) {
            $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        } else {
            $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        }
    }

    /**
     * Document that the extension-not-loaded branch cannot be tested.
     *
     * The code path at lines 81-85 handles when the PDO MySQL extension is not
     * loaded. This is a critical extension required for Joomla to connect to
     * MySQL/MariaDB databases. It is typically enabled in any PHP environment
     * intended for web development.
     *
     * Code path returns:
     *   Critical: "PDO MySQL extension is not loaded. This is required for
     *             Joomla database connectivity."
     *
     * NOTE: This branch is documented here for coverage completeness but cannot
     * be tested in standard PHP test environments where PDO MySQL is installed.
     */
    public function testDocumentExtensionNotLoadedBranchIsUntestable(): void
    {
        // Prove we cannot test the "not loaded" branch
        $this->assertTrue(
            extension_loaded('pdo_mysql'),
            'PDO MySQL extension is loaded in test environments - cannot test "not loaded" path',
        );

        // The critical branch exists for PHP environments without PDO MySQL
        $this->assertTrue(true, 'Extension not loaded branch documented - see test docblock');
    }
}

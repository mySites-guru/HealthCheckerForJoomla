<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Users;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\DuplicateEmailsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DuplicateEmailsCheck::class)]
class DuplicateEmailsCheckTest extends TestCase
{
    private DuplicateEmailsCheck $duplicateEmailsCheck;

    protected function setUp(): void
    {
        $this->duplicateEmailsCheck = new DuplicateEmailsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.duplicate_emails', $this->duplicateEmailsCheck->getSlug());
    }

    public function testGetCategoryReturnsUsers(): void
    {
        $this->assertSame('users', $this->duplicateEmailsCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->duplicateEmailsCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->duplicateEmailsCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->duplicateEmailsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunReturnsGoodWhenNoDuplicates(): void
    {
        $database = MockDatabaseFactory::createWithObjectList([]);
        $this->duplicateEmailsCheck->setDatabase($database);

        $healthCheckResult = $this->duplicateEmailsCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No duplicate', $healthCheckResult->description);
    }

    public function testRunReturnsWarningWhenDuplicatesFound(): void
    {
        $duplicates = [
            (object) [
                'email' => 'test@example.com',
                'cnt' => 2,
            ],
            (object) [
                'email' => 'another@example.com',
                'cnt' => 3,
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($duplicates);
        $this->duplicateEmailsCheck->setDatabase($database);

        $healthCheckResult = $this->duplicateEmailsCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('2 email address', $healthCheckResult->description);
    }
}

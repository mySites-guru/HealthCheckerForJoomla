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
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Users\UserNotesCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserNotesCheck::class)]
class UserNotesCheckTest extends TestCase
{
    private UserNotesCheck $userNotesCheck;

    protected function setUp(): void
    {
        $this->userNotesCheck = new UserNotesCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('users.user_notes', $this->userNotesCheck->getSlug());
    }

    public function testGetCategoryReturnsUsers(): void
    {
        $this->assertSame('users', $this->userNotesCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->userNotesCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->userNotesCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->userNotesCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
    }

    public function testRunWithNoNotesReturnsGood(): void
    {
        // First query: total notes = 0
        // Second query: users with notes = 0
        $database = MockDatabaseFactory::createWithSequentialResults([0, 0]);
        $this->userNotesCheck->setDatabase($database);

        $healthCheckResult = $this->userNotesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No user notes', $healthCheckResult->description);
    }

    public function testRunWithNotesReturnsGood(): void
    {
        // First query: total notes = 10
        // Second query: users with notes = 5
        $database = MockDatabaseFactory::createWithSequentialResults([10, 5]);
        $this->userNotesCheck->setDatabase($database);

        $healthCheckResult = $this->userNotesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('10 user note', $healthCheckResult->description);
        $this->assertStringContainsString('5 user', $healthCheckResult->description);
    }

    public function testRunWithSingleNoteReturnsGood(): void
    {
        // First query: total notes = 1
        // Second query: users with notes = 1
        $database = MockDatabaseFactory::createWithSequentialResults([1, 1]);
        $this->userNotesCheck->setDatabase($database);

        $healthCheckResult = $this->userNotesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('1 user note', $healthCheckResult->description);
        $this->assertStringContainsString('1 user', $healthCheckResult->description);
    }

    public function testRunWithManyNotesForFewUsersReturnsGood(): void
    {
        // Many notes concentrated on few users
        // First query: total notes = 50
        // Second query: users with notes = 3
        $database = MockDatabaseFactory::createWithSequentialResults([50, 3]);
        $this->userNotesCheck->setDatabase($database);

        $healthCheckResult = $this->userNotesCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('50 user note', $healthCheckResult->description);
        $this->assertStringContainsString('3 user', $healthCheckResult->description);
    }
}

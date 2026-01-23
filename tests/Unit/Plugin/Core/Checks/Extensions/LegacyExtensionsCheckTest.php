<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Extensions;

use HealthChecker\Tests\Utilities\MockDatabaseFactory;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\LegacyExtensionsCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LegacyExtensionsCheck::class)]
class LegacyExtensionsCheckTest extends TestCase
{
    private LegacyExtensionsCheck $check;

    protected function setUp(): void
    {
        $this->check = new LegacyExtensionsCheck();
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.legacy_extensions', $this->check->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->check->getCategory());
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

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('database', strtolower($result->description));
    }

    public function testRunWithNoExtensionsReturnsGood(): void
    {
        $database = MockDatabaseFactory::createWithObjectList([]);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithRecentExtensionsReturnsGood(): void
    {
        $recentDate = (new \DateTime())->modify('-1 year')
            ->format('F Y');
        $extensions = [
            (object) [
                'name' => 'Third Party Extension',
                'element' => 'com_thirdparty',
                'manifest_cache' => json_encode([
                    'creationDate' => $recentDate,
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithOldExtensionReturnsWarning(): void
    {
        $oldDate = (new \DateTime())->modify('-3 years')
            ->format('F Y');
        $extensions = [
            (object) [
                'name' => 'Old Extension',
                'element' => 'com_oldextension',
                'manifest_cache' => json_encode([
                    'creationDate' => $oldDate,
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('Old Extension', $result->description);
    }

    public function testRunSkipsCoreExtensions(): void
    {
        // com_content is a core extension and should be skipped
        $oldDate = (new \DateTime())->modify('-5 years')
            ->format('F Y');
        $extensions = [
            (object) [
                'name' => 'Content',
                'element' => 'com_content',
                'manifest_cache' => json_encode([
                    'creationDate' => $oldDate,
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithMultipleOldExtensionsReturnsWarning(): void
    {
        $oldDate = (new \DateTime())->modify('-3 years')
            ->format('F Y');
        $extensions = [];

        for ($i = 1; $i <= 15; $i++) {
            $extensions[] = (object) [
                'name' => "Old Extension {$i}",
                'element' => "com_old{$i}",
                'manifest_cache' => json_encode([
                    'creationDate' => $oldDate,
                ]),
            ];
        }

        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('15', $result->description);
    }

    public function testRunWithInvalidManifestCacheSkipsExtension(): void
    {
        $extensions = [
            (object) [
                'name' => 'Invalid Manifest Extension',
                'element' => 'com_invalid',
                'manifest_cache' => 'not-valid-json{',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithEmptyManifestCacheSkipsExtension(): void
    {
        $extensions = [
            (object) [
                'name' => 'Empty Manifest Extension',
                'element' => 'com_empty',
                'manifest_cache' => json_encode([]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithMissingCreationDateSkipsExtension(): void
    {
        $extensions = [
            (object) [
                'name' => 'No Date Extension',
                'element' => 'com_nodate',
                'manifest_cache' => json_encode([
                    'version' => '1.0.0',
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunHandlesVariousDateFormats(): void
    {
        $extensions = [
            (object) [
                'name' => 'Extension with Y-m-d date',
                'element' => 'com_ymd',
                'manifest_cache' => json_encode([
                    'creationDate' => '2020-01-15',
                ]),
            ],
            (object) [
                'name' => 'Extension with F Y date',
                'element' => 'com_fy',
                'manifest_cache' => json_encode([
                    'creationDate' => 'January 2020',
                ]),
            ],
            (object) [
                'name' => 'Extension with year only',
                'element' => 'com_year',
                'manifest_cache' => json_encode([
                    'creationDate' => '2020',
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // All three have old dates, so should return warning
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
    }

    public function testCheckNeverReturnsCritical(): void
    {
        $result = $this->check->run();

        $this->assertNotSame(HealthStatus::Critical, $result->healthStatus);
    }

    public function testRunWithUnparseableDateSkipsExtension(): void
    {
        $extensions = [
            (object) [
                'name' => 'Extension with bad date',
                'element' => 'com_baddate',
                'manifest_cache' => json_encode([
                    'creationDate' => 'not a valid date format xyz123',
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Extension with unparseable date should be skipped
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithEmptyCreationDateStringSkipsExtension(): void
    {
        $extensions = [
            (object) [
                'name' => 'Extension with empty date',
                'element' => 'com_emptydate',
                'manifest_cache' => json_encode([
                    'creationDate' => '',
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithNonStringCreationDateSkipsExtension(): void
    {
        $extensions = [
            (object) [
                'name' => 'Extension with numeric date',
                'element' => 'com_numericdate',
                'manifest_cache' => json_encode([
                    'creationDate' => 12345,
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithVariousDateFormatsDetectsOldExtensions(): void
    {
        // Test different date formats that should all be parsed correctly
        $extensions = [
            (object) [
                'name' => 'Extension d-m-Y',
                'element' => 'com_dmy',
                'manifest_cache' => json_encode([
                    'creationDate' => '15-01-2020',
                ]),
            ],
            (object) [
                'name' => 'Extension m/d/Y',
                'element' => 'com_mdy',
                'manifest_cache' => json_encode([
                    'creationDate' => '01/15/2020',
                ]),
            ],
            (object) [
                'name' => 'Extension d F Y',
                'element' => 'com_dfy',
                'manifest_cache' => json_encode([
                    'creationDate' => '15 January 2020',
                ]),
            ],
            (object) [
                'name' => 'Extension F d, Y',
                'element' => 'com_fdy',
                'manifest_cache' => json_encode([
                    'creationDate' => 'January 15, 2020',
                ]),
            ],
            (object) [
                'name' => 'Extension M Y',
                'element' => 'com_my',
                'manifest_cache' => json_encode([
                    'creationDate' => 'Jan 2020',
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // All have dates from 2020 (>2 years old), so should return warning
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('5', $result->description);
    }

    public function testRunSkipsAllCoreExtensionTypes(): void
    {
        // Test various core extension types that should be skipped
        $oldDate = (new \DateTime())->modify('-5 years')
            ->format('F Y');
        $extensions = [
            (object) [
                'name' => 'Content',
                'element' => 'com_content',
                'manifest_cache' => json_encode([
                    'creationDate' => $oldDate,
                ]),
            ],
            (object) [
                'name' => 'Menu Module',
                'element' => 'mod_menu',
                'manifest_cache' => json_encode([
                    'creationDate' => $oldDate,
                ]),
            ],
            (object) [
                'name' => 'TinyMCE',
                'element' => 'tinymce',
                'manifest_cache' => json_encode([
                    'creationDate' => $oldDate,
                ]),
            ],
            (object) [
                'name' => 'Cache Plugin',
                'element' => 'cache',
                'manifest_cache' => json_encode([
                    'creationDate' => $oldDate,
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // All core extensions should be skipped, so result should be Good
        $this->assertSame(HealthStatus::Good, $result->healthStatus);
    }

    public function testRunWithMoreThan20LegacyExtensionsTruncatesOutput(): void
    {
        $oldDate = (new \DateTime())->modify('-3 years')
            ->format('F Y');
        $extensions = [];

        for ($i = 1; $i <= 25; $i++) {
            $extensions[] = (object) [
                'name' => "Legacy Extension {$i}",
                'element' => "com_legacy{$i}",
                'manifest_cache' => json_encode([
                    'creationDate' => $oldDate,
                ]),
            ];
        }

        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('25', $result->description);
        // With more than 20, it should show '...' and have a different message format
        $this->assertStringContainsString('...', $result->description);
    }

    public function testRunWithMixedValidAndInvalidManifests(): void
    {
        $oldDate = (new \DateTime())->modify('-3 years')
            ->format('F Y');
        $extensions = [
            // Valid old extension
            (object) [
                'name' => 'Old Third Party',
                'element' => 'com_oldthirdparty',
                'manifest_cache' => json_encode([
                    'creationDate' => $oldDate,
                ]),
            ],
            // Invalid JSON
            (object) [
                'name' => 'Invalid JSON',
                'element' => 'com_invalid',
                'manifest_cache' => '{broken json',
            ],
            // Empty array manifest
            (object) [
                'name' => 'Empty Manifest',
                'element' => 'com_empty',
                'manifest_cache' => json_encode([]),
            ],
            // Null manifest
            (object) [
                'name' => 'Null Manifest',
                'element' => 'com_null',
                'manifest_cache' => '',
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Only the valid old extension should be counted
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('1 third-party extension', $result->description);
        $this->assertStringContainsString('Old Third Party', $result->description);
    }

    public function testRunWithStrtotimeFallbackDateFormat(): void
    {
        // Use a date format that DateTime::createFromFormat doesn't handle
        // but strtotime can parse (e.g., "1st June 2020")
        $extensions = [
            (object) [
                'name' => 'Extension with strtotime date',
                'element' => 'com_strtimedate',
                'manifest_cache' => json_encode([
                    'creationDate' => '1st June 2020',
                ]),
            ],
        ];
        $database = MockDatabaseFactory::createWithObjectList($extensions);
        $this->check->setDatabase($database);

        $result = $this->check->run();

        // Date "1st June 2020" is old (>2 years) so should return warning
        $this->assertSame(HealthStatus::Warning, $result->healthStatus);
        $this->assertStringContainsString('strtotime date', $result->description);
    }
}

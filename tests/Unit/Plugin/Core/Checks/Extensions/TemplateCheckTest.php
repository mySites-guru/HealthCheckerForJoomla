<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit\Plugin\Core\Checks\Extensions;

use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use MySitesGuru\HealthChecker\Component\Administrator\Check\HealthStatus;
use MySitesGuru\HealthChecker\Plugin\Core\Checks\Extensions\TemplateCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TemplateCheck::class)]
class TemplateCheckTest extends TestCase
{
    private TemplateCheck $templateCheck;

    protected function setUp(): void
    {
        $this->templateCheck = new TemplateCheck();
        $this->cleanupTemplateDirectories();
    }

    protected function tearDown(): void
    {
        $this->cleanupTemplateDirectories();
    }

    private function cleanupTemplateDirectories(): void
    {
        $dirs = [
            JPATH_SITE . '/templates/cassiopeia',
            JPATH_SITE . '/templates/test_template',
            JPATH_SITE . '/templates/invalid_xml_template',
            JPATH_SITE . '/templates/missing_index_template',
            JPATH_SITE . '/templates',
            JPATH_ADMINISTRATOR . '/templates/atum',
            JPATH_ADMINISTRATOR . '/templates/admin_test',
            JPATH_ADMINISTRATOR . '/templates',
        ];

        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                $files = glob($dir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }

                @rmdir($dir);
            }
        }
    }

    private function createTemplateDirectory(
        string $path,
        bool $createXml = true,
        bool $createIndex = true,
        bool $validXml = true,
    ): void {
        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }

        if ($createXml) {
            $xmlContent = $validXml
                ? "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<extension type=\"template\"><name>Test</name></extension>"
                : 'This is not valid XML';
            file_put_contents($path . '/templateDetails.xml', $xmlContent);
        }

        if ($createIndex) {
            file_put_contents($path . '/index.php', '<?php defined("_JEXEC") or die;');
        }
    }

    public function testGetSlugReturnsCorrectValue(): void
    {
        $this->assertSame('extensions.template', $this->templateCheck->getSlug());
    }

    public function testGetCategoryReturnsExtensions(): void
    {
        $this->assertSame('extensions', $this->templateCheck->getCategory());
    }

    public function testGetProviderReturnsCore(): void
    {
        $this->assertSame('core', $this->templateCheck->getProvider());
    }

    public function testGetTitleReturnsString(): void
    {
        $title = $this->templateCheck->getTitle();

        $this->assertIsString($title);
        $this->assertNotEmpty($title);
    }

    public function testRunWithoutDatabaseReturnsWarning(): void
    {
        $healthCheckResult = $this->templateCheck->run();

        $this->assertSame(HealthStatus::Warning, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('database', strtolower($healthCheckResult->description));
    }

    public function testRunWithValidTemplatesReturnsGood(): void
    {
        // Create valid template directories
        $this->createTemplateDirectory(JPATH_SITE . '/templates/cassiopeia');
        $this->createTemplateDirectory(JPATH_ADMINISTRATOR . '/templates/atum');

        $database = $this->createDatabaseWithTemplates(
            (object) [
                'template' => 'cassiopeia',
                'title' => 'Cassiopeia',
            ],
            (object) [
                'template' => 'atum',
                'title' => 'Atum',
            ],
        );
        $this->templateCheck->setDatabase($database);

        $healthCheckResult = $this->templateCheck->run();

        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('cassiopeia', $healthCheckResult->description);
        $this->assertStringContainsString('atum', $healthCheckResult->description);
    }

    public function testRunWithNoSiteTemplateConfiguredReturnsCritical(): void
    {
        $this->createTemplateDirectory(JPATH_ADMINISTRATOR . '/templates/atum');

        $database = $this->createDatabaseWithTemplates(null, (object) [
            'template' => 'atum',
            'title' => 'Atum',
        ]);
        $this->templateCheck->setDatabase($database);

        $healthCheckResult = $this->templateCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No default site template', $healthCheckResult->description);
    }

    public function testRunWithNoAdminTemplateConfiguredReturnsCritical(): void
    {
        $this->createTemplateDirectory(JPATH_SITE . '/templates/cassiopeia');

        $database = $this->createDatabaseWithTemplates(
            (object) [
                'template' => 'cassiopeia',
                'title' => 'Cassiopeia',
            ],
            null,
        );
        $this->templateCheck->setDatabase($database);

        $healthCheckResult = $this->templateCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('No default admin template', $healthCheckResult->description);
    }

    public function testRunWithMissingSiteTemplateDirectoryReturnsCritical(): void
    {
        $this->createTemplateDirectory(JPATH_ADMINISTRATOR . '/templates/atum');
        // Site template directory does NOT exist

        $database = $this->createDatabaseWithTemplates(
            (object) [
                'template' => 'nonexistent',
                'title' => 'Missing',
            ],
            (object) [
                'template' => 'atum',
                'title' => 'Atum',
            ],
        );
        $this->templateCheck->setDatabase($database);

        $healthCheckResult = $this->templateCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('directory not found', $healthCheckResult->description);
    }

    public function testRunWithMissingTemplateDetailsXmlReturnsCritical(): void
    {
        // Create template without XML
        $this->createTemplateDirectory(JPATH_SITE . '/templates/test_template', false, true);
        $this->createTemplateDirectory(JPATH_ADMINISTRATOR . '/templates/atum');

        $database = $this->createDatabaseWithTemplates(
            (object) [
                'template' => 'test_template',
                'title' => 'Test',
            ],
            (object) [
                'template' => 'atum',
                'title' => 'Atum',
            ],
        );
        $this->templateCheck->setDatabase($database);

        $healthCheckResult = $this->templateCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('missing templateDetails.xml', $healthCheckResult->description);
    }

    public function testRunWithInvalidXmlReturnsCritical(): void
    {
        // Create template with invalid XML
        $this->createTemplateDirectory(JPATH_SITE . '/templates/invalid_xml_template', true, true, false);
        $this->createTemplateDirectory(JPATH_ADMINISTRATOR . '/templates/atum');

        $database = $this->createDatabaseWithTemplates(
            (object) [
                'template' => 'invalid_xml_template',
                'title' => 'Invalid',
            ],
            (object) [
                'template' => 'atum',
                'title' => 'Atum',
            ],
        );
        $this->templateCheck->setDatabase($database);

        $healthCheckResult = $this->templateCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('invalid templateDetails.xml', $healthCheckResult->description);
    }

    public function testRunWithMissingIndexPhpReturnsCritical(): void
    {
        // Create template without index.php
        $this->createTemplateDirectory(JPATH_SITE . '/templates/missing_index_template', true, false);
        $this->createTemplateDirectory(JPATH_ADMINISTRATOR . '/templates/atum');

        $database = $this->createDatabaseWithTemplates(
            (object) [
                'template' => 'missing_index_template',
                'title' => 'No Index',
            ],
            (object) [
                'template' => 'atum',
                'title' => 'Atum',
            ],
        );
        $this->templateCheck->setDatabase($database);

        $healthCheckResult = $this->templateCheck->run();

        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('missing index.php', $healthCheckResult->description);
    }

    public function testCheckNeverReturnsWarningStatus(): void
    {
        // This check only returns Good or Critical (per docblock)
        $this->createTemplateDirectory(JPATH_SITE . '/templates/cassiopeia');
        $this->createTemplateDirectory(JPATH_ADMINISTRATOR . '/templates/atum');

        $database = $this->createDatabaseWithTemplates(
            (object) [
                'template' => 'cassiopeia',
                'title' => 'Cassiopeia',
            ],
            (object) [
                'template' => 'atum',
                'title' => 'Atum',
            ],
        );
        $this->templateCheck->setDatabase($database);

        $healthCheckResult = $this->templateCheck->run();

        // When templates are valid, should return Good
        $this->assertSame(HealthStatus::Good, $healthCheckResult->healthStatus);
    }

    public function testRunWithAdminTemplateIssuesMergesWithSiteIssues(): void
    {
        // Create valid site template
        $this->createTemplateDirectory(JPATH_SITE . '/templates/cassiopeia');
        // Create admin template with invalid XML (this will trigger admin issues)
        $this->createTemplateDirectory(JPATH_ADMINISTRATOR . '/templates/admin_test', true, true, false);

        $database = $this->createDatabaseWithTemplates(
            (object) [
                'template' => 'cassiopeia',
                'title' => 'Cassiopeia',
            ],
            (object) [
                'template' => 'admin_test',
                'title' => 'Admin Test',
            ],
        );
        $this->templateCheck->setDatabase($database);

        $healthCheckResult = $this->templateCheck->run();

        // Should return critical because admin template has invalid XML
        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        $this->assertStringContainsString('invalid templateDetails.xml', $healthCheckResult->description);
        $this->assertStringContainsString('Admin', $healthCheckResult->description);
    }

    public function testRunWithBothTemplatesHavingIssuesReportsBoth(): void
    {
        // Create site template without index.php
        $this->createTemplateDirectory(JPATH_SITE . '/templates/cassiopeia', true, false);
        // Create admin template without templateDetails.xml
        $this->createTemplateDirectory(JPATH_ADMINISTRATOR . '/templates/atum', false, true);

        $database = $this->createDatabaseWithTemplates(
            (object) [
                'template' => 'cassiopeia',
                'title' => 'Cassiopeia',
            ],
            (object) [
                'template' => 'atum',
                'title' => 'Atum',
            ],
        );
        $this->templateCheck->setDatabase($database);

        $healthCheckResult = $this->templateCheck->run();

        // Should return critical with both issues merged
        $this->assertSame(HealthStatus::Critical, $healthCheckResult->healthStatus);
        // Should contain both site and admin issues
        $this->assertStringContainsString('Site', $healthCheckResult->description);
        $this->assertStringContainsString('Admin', $healthCheckResult->description);
    }

    /**
     * Create a mock database that returns site and admin templates
     */
    private function createDatabaseWithTemplates(?object $siteTemplate, ?object $adminTemplate): DatabaseInterface
    {
        return new class ($siteTemplate, $adminTemplate) implements DatabaseInterface {
            private int $queryIndex = 0;

            public function __construct(
                private readonly ?object $siteTemplate,
                private readonly ?object $adminTemplate,
            ) {}

            public function getVersion(): string
            {
                return '8.0.30';
            }

            public function getQuery(bool $new = false): QueryInterface
            {
                return $this->createMockQuery();
            }

            public function setQuery(QueryInterface|string $query, int $offset = 0, int $limit = 0): self
            {
                return $this;
            }

            public function loadResult(): mixed
            {
                return null;
            }

            public function loadColumn(): array
            {
                return [];
            }

            public function loadAssoc(): ?array
            {
                return null;
            }

            public function loadAssocList(string $key = '', string $column = ''): array
            {
                return [];
            }

            public function loadObject(): ?object
            {
                // First call returns site template, second returns admin template
                $result = $this->queryIndex === 0 ? $this->siteTemplate : $this->adminTemplate;
                $this->queryIndex++;

                return $result;
            }

            public function loadObjectList(): array
            {
                return [];
            }

            public function execute(): bool
            {
                return true;
            }

            public function quoteName(array|string $name, ?string $as = null): string
            {
                return is_array($name) ? '' : $name;
            }

            public function quote(array|string $text, bool $escape = true): string
            {
                return is_string($text) ? sprintf('"%s"', $text) : '';
            }

            public function getPrefix(): string
            {
                return '#__';
            }

            public function getNullDate(): string
            {
                return '0000-00-00 00:00:00';
            }

            public function getTableList(): array
            {
                return [];
            }

            private function createMockQuery(): QueryInterface
            {
                return new class implements QueryInterface {
                    public function select(array|string $columns): self
                    {
                        return $this;
                    }

                    public function from(string $table, ?string $alias = null): self
                    {
                        return $this;
                    }

                    public function where(array|string $conditions): self
                    {
                        return $this;
                    }

                    public function join(string $type, string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function leftJoin(string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function innerJoin(string $table, string $condition = ''): self
                    {
                        return $this;
                    }

                    public function order(array|string $columns): self
                    {
                        return $this;
                    }

                    public function group(array|string $columns): self
                    {
                        return $this;
                    }

                    public function having(array|string $conditions): self
                    {
                        return $this;
                    }

                    public function __toString(): string
                    {
                        return '';
                    }
                };
            }
        };
    }
}

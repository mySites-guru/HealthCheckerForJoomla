<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

namespace HealthChecker\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test that all XML manifest files are valid and correctly structured
 *
 * Validates:
 * - XML is well-formed and parseable
 * - Required elements are present
 * - Namespace declarations match expected MySitesGuru structure
 * - Version numbers follow semantic versioning (MAJOR.MINOR.PATCH)
 * - Author information is present
 * - File structure references are valid
 *
 * @since 1.0.0
 */
class ManifestXmlValidationTest extends TestCase
{
    /**
     * Semantic versioning regex pattern (MAJOR.MINOR.PATCH with optional pre-release/build metadata)
     * Examples: 1.0.0, 2.1.3, 1.0.0-alpha, 1.0.0-beta.1, 1.0.0+build.123
     */
    private const SEMVER_PATTERN = '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-((?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*)(?:\.(?:0|[1-9]\d*|\d*[a-zA-Z-][0-9a-zA-Z-]*))*))?(?:\+([0-9a-zA-Z-]+(?:\.[0-9a-zA-Z-]+)*))?$/';

    private const EXPECTED_AUTHOR = 'Phil E. Taylor';

    private const EXPECTED_AUTHOR_EMAIL = 'phil@phil-taylor.com';

    private const EXPECTED_LICENSE = 'GNU General Public License version 2 or later; see LICENSE.txt';

    private const BASE_PATH = __DIR__ . '/../../healthchecker';

    /**
     * Data provider for all manifest XML files
     *
     * @return array<string, array{string, string, string}>
     */
    public static function xmlManifestProvider(): array
    {
        return [
            'component' => [
                self::BASE_PATH . '/component/healthchecker.xml',
                'component',
                'MySitesGuru\HealthChecker\Component',
            ],
            'module' => [
                self::BASE_PATH . '/module/mod_healthchecker.xml',
                'module',
                'MySitesGuru\HealthChecker\Module',
            ],
            'plugin_core' => [
                self::BASE_PATH . '/plugins/core/core.xml',
                'plugin',
                'MySitesGuru\HealthChecker\Plugin\Core',
            ],
            'plugin_example' => [
                self::BASE_PATH . '/plugins/example/example.xml',
                'plugin',
                'MySitesGuru\HealthChecker\Plugin\Example',
            ],
            'plugin_akeebabackup' => [
                self::BASE_PATH . '/plugins/akeebabackup/akeebabackup.xml',
                'plugin',
                'MySitesGuru\HealthChecker\Plugin\AkeebaBackup',
            ],
            'plugin_akeebaadmintools' => [
                self::BASE_PATH . '/plugins/akeebaadmintools/akeebaadmintools.xml',
                'plugin',
                'MySitesGuru\HealthChecker\Plugin\AkeebaAdminTools',
            ],
            'plugin_mysitesguru' => [
                self::BASE_PATH . '/plugins/mysitesguru/mysitesguru.xml',
                'plugin',
                'MySitesGuru\HealthChecker\Plugin\MySitesGuru',
            ],
        ];
    }

    /**
     * Data provider for component and module manifests (non-plugin extensions)
     *
     * @return array<string, array{string, string, string}>
     */
    public static function componentAndModuleManifestProvider(): array
    {
        return [
            'component' => [
                self::BASE_PATH . '/component/healthchecker.xml',
                'component',
                'MySitesGuru\HealthChecker\Component',
            ],
            'module' => [
                self::BASE_PATH . '/module/mod_healthchecker.xml',
                'module',
                'MySitesGuru\HealthChecker\Module',
            ],
        ];
    }

    /**
     * Data provider for plugin manifests only
     *
     * @return array<string, array{string, string, string}>
     */
    public static function pluginManifestProvider(): array
    {
        return [
            'plugin_core' => [
                self::BASE_PATH . '/plugins/core/core.xml',
                'plugin',
                'MySitesGuru\HealthChecker\Plugin\Core',
            ],
            'plugin_example' => [
                self::BASE_PATH . '/plugins/example/example.xml',
                'plugin',
                'MySitesGuru\HealthChecker\Plugin\Example',
            ],
            'plugin_akeebabackup' => [
                self::BASE_PATH . '/plugins/akeebabackup/akeebabackup.xml',
                'plugin',
                'MySitesGuru\HealthChecker\Plugin\AkeebaBackup',
            ],
            'plugin_akeebaadmintools' => [
                self::BASE_PATH . '/plugins/akeebaadmintools/akeebaadmintools.xml',
                'plugin',
                'MySitesGuru\HealthChecker\Plugin\AkeebaAdminTools',
            ],
            'plugin_mysitesguru' => [
                self::BASE_PATH . '/plugins/mysitesguru/mysitesguru.xml',
                'plugin',
                'MySitesGuru\HealthChecker\Plugin\MySitesGuru',
            ],
        ];
    }

    /**
     * Data provider for component manifest only
     *
     * @return array<string, array{string, string, string}>
     */
    public static function componentManifestProvider(): array
    {
        return [
            'component' => [
                self::BASE_PATH . '/component/healthchecker.xml',
                'component',
                'MySitesGuru\HealthChecker\Component',
            ],
        ];
    }

    /**
     * Data provider for module manifest only
     *
     * @return array<string, array{string, string, string}>
     */
    public static function moduleManifestProvider(): array
    {
        return [
            'module' => [
                self::BASE_PATH . '/module/mod_healthchecker.xml',
                'module',
                'MySitesGuru\HealthChecker\Module',
            ],
        ];
    }

    /**
     * Test that XML file exists and is readable
     */
    #[DataProvider('xmlManifestProvider')]
    public function testXmlFileExists(string $xmlPath, string $extensionType, string $expectedNamespace): void
    {
        $this->assertFileExists($xmlPath, 'XML manifest file should exist: ' . $xmlPath);
        $this->assertFileIsReadable($xmlPath, 'XML manifest file should be readable: ' . $xmlPath);
    }

    /**
     * Test that XML is well-formed and parseable
     */
    #[DataProvider('xmlManifestProvider')]
    public function testXmlIsWellFormed(string $xmlPath, string $extensionType, string $expectedNamespace): void
    {
        // Suppress libxml errors temporarily
        $useErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $xml = simplexml_load_file($xmlPath);

        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($useErrors);

        $this->assertEmpty(
            $errors,
            sprintf('XML should be well-formed without errors in %s: ', $xmlPath) . $this->formatXmlErrors($errors),
        );
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml, 'XML should be parseable: ' . $xmlPath);
    }

    /**
     * Test that extension type attribute is correct
     */
    #[DataProvider('xmlManifestProvider')]
    public function testExtensionTypeIsCorrect(string $xmlPath, string $extensionType, string $expectedNamespace): void
    {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        $type = (string) $xml['type'];
        $this->assertSame(
            $extensionType,
            $type,
            sprintf("Extension type should be '%s' in %s", $extensionType, $xmlPath),
        );
    }

    /**
     * Test that namespace declaration exists and matches expected pattern
     */
    #[DataProvider('xmlManifestProvider')]
    public function testNamespaceDeclarationIsCorrect(
        string $xmlPath,
        string $extensionType,
        string $expectedNamespace,
    ): void {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        $namespace = $xml->xpath('//namespace');
        $this->assertNotEmpty($namespace, 'Namespace element should exist in ' . $xmlPath);
        $this->assertCount(1, $namespace, 'Should have exactly one namespace declaration in ' . $xmlPath);

        $actualNamespace = (string) $namespace[0];
        $this->assertSame(
            $expectedNamespace,
            $actualNamespace,
            sprintf("Namespace should be '%s' in %s", $expectedNamespace, $xmlPath),
        );

        // Verify path attribute exists
        $path = (string) $namespace[0]['path'];
        $this->assertSame('src', $path, "Namespace path should be 'src' in " . $xmlPath);
    }

    /**
     * Test that namespace does NOT contain \Administrator suffix in XML
     *
     * Component and module namespaces should be base namespaces in XML.
     * Joomla automatically adds \Administrator for admin-side extensions.
     */
    #[DataProvider('componentAndModuleManifestProvider')]
    public function testNamespaceDoesNotContainAdministratorSuffix(
        string $xmlPath,
        string $extensionType,
        string $expectedNamespace,
    ): void {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        $namespace = $xml->xpath('//namespace');
        $actualNamespace = (string) $namespace[0];

        $this->assertStringNotContainsString(
            '\\Administrator',
            $actualNamespace,
            sprintf("XML namespace should NOT contain '\\Administrator' suffix in %s. ", $xmlPath) .
            'Joomla adds this automatically for admin-side extensions.',
        );
    }

    /**
     * Test that all namespaces start with MySitesGuru\HealthChecker
     */
    #[DataProvider('xmlManifestProvider')]
    public function testNamespaceUsesCorrectVendorPrefix(
        string $xmlPath,
        string $extensionType,
        string $expectedNamespace,
    ): void {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        $namespace = $xml->xpath('//namespace');
        $actualNamespace = (string) $namespace[0];

        $this->assertStringStartsWith(
            'MySitesGuru\\HealthChecker\\',
            $actualNamespace,
            'Namespace should start with \'MySitesGuru\HealthChecker\\\' in ' . $xmlPath,
        );
    }

    /**
     * Test that version number is present and follows semantic versioning
     */
    #[DataProvider('xmlManifestProvider')]
    public function testVersionNumberIsConsistent(
        string $xmlPath,
        string $extensionType,
        string $expectedNamespace,
    ): void {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        $version = (string) $xml->version;
        $this->assertNotEmpty($version, 'Version should be present in ' . $xmlPath);
        $this->assertMatchesRegularExpression(
            self::SEMVER_PATTERN,
            $version,
            'Version should follow semantic versioning (MAJOR.MINOR.PATCH) in ' . $xmlPath . ' (got: ' . $version . ')',
        );
    }

    /**
     * Test that author information is present and correct
     */
    #[DataProvider('xmlManifestProvider')]
    public function testAuthorInformationIsPresent(
        string $xmlPath,
        string $extensionType,
        string $expectedNamespace,
    ): void {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        $author = (string) $xml->author;
        $authorEmail = (string) $xml->authorEmail;

        $this->assertNotEmpty($author, 'Author should be present in ' . $xmlPath);
        $this->assertNotEmpty($authorEmail, 'Author email should be present in ' . $xmlPath);

        $this->assertStringContainsString(
            self::EXPECTED_AUTHOR,
            $author,
            "Author should contain '" . self::EXPECTED_AUTHOR . ("' in " . $xmlPath),
        );
        $this->assertSame(
            self::EXPECTED_AUTHOR_EMAIL,
            $authorEmail,
            'Author email should be ' . self::EXPECTED_AUTHOR_EMAIL . (' in ' . $xmlPath),
        );
    }

    /**
     * Test that license information is present and correct
     */
    #[DataProvider('xmlManifestProvider')]
    public function testLicenseInformationIsCorrect(
        string $xmlPath,
        string $extensionType,
        string $expectedNamespace,
    ): void {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        $license = (string) $xml->license;
        $this->assertNotEmpty($license, 'License should be present in ' . $xmlPath);
        $this->assertSame(self::EXPECTED_LICENSE, $license, 'License should be GPL v2+ in ' . $xmlPath);
    }

    /**
     * Test that description element exists
     */
    #[DataProvider('xmlManifestProvider')]
    public function testDescriptionExists(string $xmlPath, string $extensionType, string $expectedNamespace): void
    {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        $description = (string) $xml->description;
        $this->assertNotEmpty($description, 'Description should be present in ' . $xmlPath);

        // Description should be a language key
        $this->assertMatchesRegularExpression(
            '/^[A-Z_]+$/',
            $description,
            'Description should be a language constant in ' . $xmlPath,
        );
    }

    /**
     * Test that files/folders section exists
     */
    #[DataProvider('xmlManifestProvider')]
    public function testFilesStructureExists(string $xmlPath, string $extensionType, string $expectedNamespace): void
    {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        if ($extensionType === 'component') {
            // Component has <administration><files> structure
            $files = $xml->xpath('//administration/files');
            $this->assertNotEmpty($files, 'Component should have administration/files section in ' . $xmlPath);
        } else {
            // Modules and plugins have <files> at root
            $files = $xml->xpath('//files');
            $this->assertNotEmpty($files, 'Should have files section in ' . $xmlPath);
        }
    }

    /**
     * Test that services folder is referenced
     */
    #[DataProvider('xmlManifestProvider')]
    public function testServicesfolderIsReferenced(
        string $xmlPath,
        string $extensionType,
        string $expectedNamespace,
    ): void {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        if ($extensionType === 'component') {
            $servicesFolders = $xml->xpath('//administration/files/folder[@name="services" or text()="services"]');
        } else {
            $servicesFolders = $xml->xpath('//files/folder[@*="services" or text()="services"]');
        }

        $this->assertNotEmpty(
            $servicesFolders,
            'Services folder should be referenced in files section of ' . $xmlPath,
        );
    }

    /**
     * Test that src folder is referenced
     */
    #[DataProvider('xmlManifestProvider')]
    public function testSrcFolderIsReferenced(string $xmlPath, string $extensionType, string $expectedNamespace): void
    {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        if ($extensionType === 'component') {
            $srcFolders = $xml->xpath('//administration/files/folder[@name="src" or text()="src"]');
        } else {
            $srcFolders = $xml->xpath('//files/folder[@name="src" or text()="src"]');
        }

        $this->assertNotEmpty($srcFolders, 'Src folder should be referenced in files section of ' . $xmlPath);
    }

    /**
     * Test that update server is present
     */
    #[DataProvider('xmlManifestProvider')]
    public function testUpdateServerIsPresent(string $xmlPath, string $extensionType, string $expectedNamespace): void
    {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        $updateServers = $xml->xpath('//updateservers/server');
        $this->assertNotEmpty($updateServers, 'Update server should be present in ' . $xmlPath);

        $serverUrl = (string) $updateServers[0];
        $this->assertStringContainsString(
            'joomlahealthchecker.com',
            $serverUrl,
            'Update server should point to joomlahealthchecker.com in ' . $xmlPath,
        );
    }

    /**
     * Test that creation date is present and in correct format
     */
    #[DataProvider('xmlManifestProvider')]
    public function testCreationDateIsPresent(string $xmlPath, string $extensionType, string $expectedNamespace): void
    {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        $creationDate = (string) $xml->creationDate;
        $this->assertNotEmpty($creationDate, 'Creation date should be present in ' . $xmlPath);

        // Should be in format YYYY-MM or YYYY
        $this->assertMatchesRegularExpression(
            '/^\d{4}(-\d{2})?$/',
            $creationDate,
            'Creation date should be in YYYY or YYYY-MM format in ' . $xmlPath,
        );
    }

    /**
     * Test that plugin extensions have correct group attribute
     */
    #[DataProvider('pluginManifestProvider')]
    public function testPluginGroupIsCorrect(string $xmlPath, string $extensionType, string $expectedNamespace): void
    {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        $group = (string) $xml['group'];
        $this->assertSame('healthchecker', $group, "Plugin group should be 'healthchecker' in " . $xmlPath);
    }

    /**
     * Test that component has administration section
     */
    #[DataProvider('componentManifestProvider')]
    public function testComponentHasAdministrationSection(
        string $xmlPath,
        string $extensionType,
        string $expectedNamespace,
    ): void {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        $administration = $xml->xpath('//administration');
        $this->assertNotEmpty($administration, 'Component should have administration section in ' . $xmlPath);

        // Should have menu item
        $menu = $xml->xpath('//administration/menu');
        $this->assertNotEmpty($menu, 'Component should have menu item in administration section of ' . $xmlPath);
    }

    /**
     * Test that module has correct client attribute
     */
    #[DataProvider('moduleManifestProvider')]
    public function testModuleHasCorrectClient(
        string $xmlPath,
        string $extensionType,
        string $expectedNamespace,
    ): void {
        $xml = simplexml_load_file($xmlPath);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);

        $client = (string) $xml['client'];
        $this->assertSame('administrator', $client, "Module client should be 'administrator' in " . $xmlPath);
    }

    /**
     * Format XML errors for output
     *
     * @param array<\LibXMLError> $errors
     */
    private function formatXmlErrors(array $errors): string
    {
        if ($errors === []) {
            return '';
        }

        $output = [];
        foreach ($errors as $error) {
            $output[] = sprintf(
                '[%s] Line %d: %s',
                $error->level === LIBXML_ERR_WARNING ? 'Warning' : ($error->level === LIBXML_ERR_ERROR ? 'Error' : 'Fatal'),
                $error->line,
                trim($error->message),
            );
        }

        return "\n" . implode("\n", $output);
    }
}

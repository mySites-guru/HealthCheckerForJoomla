#!/bin/bash

# Health Checker for Joomla - Build ZIP Packages
# Builds all extension ZIP files (component, module, plugins, package)
# Can be called standalone or from release.sh

set -e

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
SOURCE_DIR="$PROJECT_ROOT/healthchecker"
BUILD_DIR="$PROJECT_ROOT/build/dist"
MANIFEST_FILE="$PROJECT_ROOT/healthchecker/component/healthchecker.xml"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get version from argument or manifest
if [ -n "$1" ]; then
    VERSION="$1"
else
    VERSION=$(grep -o '<version>[^<]*</version>' "$MANIFEST_FILE" | sed 's/<[^>]*>//g')
fi

if [ -z "$VERSION" ]; then
    echo -e "${RED}ERROR: Could not determine version. Pass version as argument or ensure manifest exists.${NC}"
    exit 1
fi

echo -e "${YELLOW}Building release packages for v${VERSION}...${NC}"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR/tmp"

# Minify assets
echo "Minifying assets..."
npx terser "$SOURCE_DIR/component/media/js/admin-report.js" -c -m -o "$SOURCE_DIR/component/media/js/admin-report.min.js" --source-map "url=admin-report.min.js.map"
npx csso-cli "$SOURCE_DIR/component/media/css/admin-report.css" -o "$SOURCE_DIR/component/media/css/admin-report.min.css" --source-map "$SOURCE_DIR/component/media/css/admin-report.min.css.map"
npx terser "$SOURCE_DIR/module/media/js/module-stats.js" -c -m -o "$SOURCE_DIR/module/media/js/module-stats.min.js" --source-map "url=module-stats.min.js.map"
npx csso-cli "$SOURCE_DIR/module/media/css/module-card.css" -o "$SOURCE_DIR/module/media/css/module-card.min.css" --source-map "$SOURCE_DIR/module/media/css/module-card.min.css.map"
echo -e "${GREEN}✓ Assets minified${NC}"

# Component
echo "Building component..."
COMP_DIR="$BUILD_DIR/tmp/com_healthchecker"
mkdir -p "$COMP_DIR"
for item in "$SOURCE_DIR/component/"*; do
    if [ "$(basename "$item")" != "media" ] && [ "$(basename "$item")" != "healthchecker.xml" ]; then
        cp -r "$item" "$COMP_DIR/"
    fi
done
cp -r "$SOURCE_DIR/component/media" "$COMP_DIR/"
cp "$SOURCE_DIR/component/healthchecker.xml" "$COMP_DIR/"
cd "$COMP_DIR"
zip -r "$BUILD_DIR/com_healthchecker-${VERSION}.zip" . -x "*.DS_Store" -x "*__MACOSX*" > /dev/null
echo -e "${GREEN}✓ com_healthchecker-${VERSION}.zip${NC}"

# Module
echo "Building module..."
MOD_DIR="$BUILD_DIR/tmp/mod_healthchecker"
mkdir -p "$MOD_DIR"
cp -r "$SOURCE_DIR/module/"* "$MOD_DIR/"
cd "$MOD_DIR"
zip -r "$BUILD_DIR/mod_healthchecker-${VERSION}.zip" . -x "*.DS_Store" -x "*__MACOSX*" > /dev/null
echo -e "${GREEN}✓ mod_healthchecker-${VERSION}.zip${NC}"

# Plugins
for plugin in core example akeebabackup akeebaadmintools mysitesguru; do
    echo "Building $plugin plugin..."
    PLG_DIR="$BUILD_DIR/tmp/plg_healthchecker_${plugin}"
    mkdir -p "$PLG_DIR"
    cp -r "$SOURCE_DIR/plugins/$plugin/"* "$PLG_DIR/"
    cd "$PLG_DIR"
    zip -r "$BUILD_DIR/plg_healthchecker_${plugin}-${VERSION}.zip" . -x "*.DS_Store" -x "*__MACOSX*" > /dev/null
    echo -e "${GREEN}✓ plg_healthchecker_${plugin}-${VERSION}.zip${NC}"
done

# Package
echo "Building unified package..."
PKG_DIR="$BUILD_DIR/tmp/pkg_healthchecker"
mkdir -p "$PKG_DIR/packages"
cp "$BUILD_DIR"/*.zip "$PKG_DIR/packages/"

# Copy package-level language files
cp -r "$SOURCE_DIR/package/language" "$PKG_DIR/"

cat > "$PKG_DIR/pkg_healthchecker.xml" << EOF
<?xml version="1.0" encoding="utf-8"?>
<extension type="package" method="upgrade">
    <name>PKG_HEALTHCHECKER</name>
    <packagename>healthchecker</packagename>
    <author>mySites.guru / Phil E. Taylor</author>
    <creationDate>$(date +%Y-%m)</creationDate>
    <copyright>(C) $(date +%Y) mySites.guru / Phil E. Taylor</copyright>
    <license>GNU General Public License version 2 or later; see LICENSE.txt</license>
    <authorEmail>phil@phil-taylor.com</authorEmail>
    <authorUrl>https://phil-taylor.com</authorUrl>
    <version>${VERSION}</version>
    <description><![CDATA[PKG_HEALTHCHECKER_XML_DESCRIPTION]]></description>
    <packager>mySites.guru</packager>
    <packagerurl>https://mysites.guru</packagerurl>
    <blockChildUninstall>true</blockChildUninstall>

    <languages folder="language">
        <language tag="en-GB">en-GB/pkg_healthchecker.ini</language>
        <language tag="en-GB">en-GB/pkg_healthchecker.sys.ini</language>
        <language tag="es-ES">es-ES/pkg_healthchecker.ini</language>
        <language tag="es-ES">es-ES/pkg_healthchecker.sys.ini</language>
        <language tag="ru-RU">ru-RU/pkg_healthchecker.ini</language>
        <language tag="ru-RU">ru-RU/pkg_healthchecker.sys.ini</language>
    </languages>

    <files folder="packages">
        <file type="component" id="com_healthchecker">com_healthchecker-${VERSION}.zip</file>
        <file type="module" id="mod_healthchecker" client="administrator">mod_healthchecker-${VERSION}.zip</file>
        <file type="plugin" id="core" group="healthchecker">plg_healthchecker_core-${VERSION}.zip</file>
        <file type="plugin" id="example" group="healthchecker">plg_healthchecker_example-${VERSION}.zip</file>
        <file type="plugin" id="akeebabackup" group="healthchecker">plg_healthchecker_akeebabackup-${VERSION}.zip</file>
        <file type="plugin" id="akeebaadmintools" group="healthchecker">plg_healthchecker_akeebaadmintools-${VERSION}.zip</file>
        <file type="plugin" id="mysitesguru" group="healthchecker">plg_healthchecker_mysitesguru-${VERSION}.zip</file>
    </files>
    <scriptfile>script.php</scriptfile>
    <updateservers>
        <server type="extension" priority="1" name="Health Checker Package">https://www.joomlahealthchecker.com/update/pkg_healthchecker.xml</server>
    </updateservers>
</extension>
EOF

cat > "$PKG_DIR/script.php" << 'SCRIPT'
<?php
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\Database\DatabaseInterface;

class Pkg_HealthcheckerInstallerScript
{
    public function preflight(string $type, InstallerAdapter $parent): bool
    {
        if (version_compare(JVERSION, '5.0.0', '<')) {
            Factory::getApplication()->enqueueMessage('Health Checker requires Joomla 5.0 or later.', 'error');
            return false;
        }
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            Factory::getApplication()->enqueueMessage('Health Checker requires PHP 8.1 or later.', 'error');
            return false;
        }
        return true;
    }

    public function uninstall(InstallerAdapter $parent): void
    {
        // Remove the plugin group directory if empty after Joomla's cascade
        $groupDir = JPATH_PLUGINS . '/healthchecker';
        if (is_dir($groupDir) && count(glob($groupDir . '/*')) === 0) {
            @rmdir($groupDir);
        }
    }

    public function postflight(string $type, InstallerAdapter $parent): void
    {
        $this->removeObsoleteFiles();

        if ($type === 'install') {
            $this->enablePlugin('healthchecker', 'core');
            $this->enablePlugin('healthchecker', 'example');
            $this->enablePlugin('healthchecker', 'mysitesguru');

            if ($this->isExtensionInstalled('component', 'com_akeebabackup')) {
                $this->enablePlugin('healthchecker', 'akeebabackup');
            }
            if ($this->isExtensionInstalled('component', 'com_admintools')) {
                $this->enablePlugin('healthchecker', 'akeebaadmintools');
            }

            $this->publishModule('mod_healthchecker', 'cpanel');

            Factory::getApplication()->enqueueMessage(
                'Health Checker installed successfully! Access it from Components > Health Checker.',
                'success'
            );
        }
    }

    private function enablePlugin(string $group, string $element): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('enabled') . ' = 1')
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote($group))
            ->where($db->quoteName('element') . ' = ' . $db->quote($element));
        $db->setQuery($query)->execute();
    }

    private function isExtensionInstalled(string $type, string $element): bool
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote($type))
            ->where($db->quoteName('element') . ' = ' . $db->quote($element))
            ->where($db->quoteName('enabled') . ' = 1');
        return (int) $db->setQuery($query)->loadResult() > 0;
    }

    private function removeObsoleteFiles(): void
    {
        $files = [
            // Removed in 3.0.38: BackupAgeCheck replaced by akeeba_backup.last_backup
            JPATH_PLUGINS . '/healthchecker/core/src/Checks/Database/BackupAgeCheck.php',
            // Removed in 3.0.36: Phantom check for non-existent plg_user_userlog
            JPATH_PLUGINS . '/healthchecker/core/src/Checks/Security/UserActionsLogCheck.php',
            // Removed in 3.0.41: Redundant and not performing well (GitHub #11)
            JPATH_PLUGINS . '/healthchecker/core/src/Checks/Extensions/LegacyExtensionsCheck.php',
        ];

        foreach ($files as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }

    private function publishModule(string $module, string $position = 'cpanel'): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__modules'))
            ->where($db->quoteName('module') . ' = ' . $db->quote($module))
            ->where($db->quoteName('client_id') . ' = 1');
        $moduleId = $db->setQuery($query)->loadResult();

        if ($moduleId) {
            $query = $db->getQuery(true)
                ->update($db->quoteName('#__modules'))
                ->set($db->quoteName('published') . ' = 1')
                ->set($db->quoteName('position') . ' = ' . $db->quote($position))
                ->set($db->quoteName('showtitle') . ' = 1')
                ->set($db->quoteName('ordering') . ' = 1')
                ->where($db->quoteName('id') . ' = ' . (int) $moduleId);
            $db->setQuery($query)->execute();

            try {
                $query = $db->getQuery(true)
                    ->insert($db->quoteName('#__modules_menu'))
                    ->columns([$db->quoteName('moduleid'), $db->quoteName('menuid')])
                    ->values((int) $moduleId . ', 0');
                $db->setQuery($query)->execute();
            } catch (\Exception $e) {
                // Already assigned
            }
        }
    }
}
SCRIPT

cd "$PKG_DIR"
zip -r "$BUILD_DIR/pkg_healthchecker-${VERSION}.zip" . -x "*.DS_Store" -x "*__MACOSX*" > /dev/null
echo -e "${GREEN}✓ pkg_healthchecker-${VERSION}.zip${NC}"

# Cleanup
rm -rf "$BUILD_DIR/tmp"
echo -e "${GREEN}✓ Packages built successfully in ${BUILD_DIR}${NC}"

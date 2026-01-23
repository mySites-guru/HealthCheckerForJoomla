<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeFromPropertyTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByParentCallTypeRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\Privatization\Rector\Property\PrivatizeFinalClassPropertyRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/healthchecker',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        // Skip language files
        '*/language/*',
        // Skip XML files
        '*.xml',
        // Skip media files
        '*/media/*',
        // Skip tmpl files (Joomla templates)
        '*/tmpl/*',
        // Skip stub files - they define empty classes for type checking only
        __DIR__ . '/tests/stubs',
        __DIR__ . '/tests/phpstan-bootstrap.php',

        // Joomla compatibility: Don't add type hints to methods that override parent classes
        // The parent Joomla classes don't use strict typing, so we must match their signatures
        AddParamTypeFromPropertyTypeRector::class => [
            __DIR__ . '/healthchecker/component/src/Controller/DisplayController.php',
            __DIR__ . '/healthchecker/component/src/View/Report/HtmlView.php',
        ],
        ParamTypeByMethodCallTypeRector::class => [
            __DIR__ . '/healthchecker/component/src/Controller/DisplayController.php',
            __DIR__ . '/healthchecker/component/src/View/Report/HtmlView.php',
        ],
        ParamTypeByParentCallTypeRector::class => [
            __DIR__ . '/healthchecker/component/src/Controller/DisplayController.php',
            __DIR__ . '/healthchecker/component/src/View/Report/HtmlView.php',
        ],

        // Joomla compatibility: Don't add type hints to $autoloadLanguage property
        // CMSPlugin parent class doesn't have a type, so we can't add one
        TypedPropertyFromAssignsRector::class => [
            __DIR__ . '/healthchecker/plugins/*/src/Extension/*Plugin.php',
        ],
        TypedPropertyFromStrictConstructorRector::class => [
            __DIR__ . '/healthchecker/plugins/*/src/Extension/*Plugin.php',
        ],

        // Joomla compatibility: Don't privatize $autoloadLanguage - must be protected
        PrivatizeFinalClassPropertyRector::class => [
            __DIR__ . '/healthchecker/plugins/*/src/Extension/*Plugin.php',
        ],
    ])
    ->withPhpSets(
        // Target PHP 8.1 - will downgrade any 8.2+ features
        php81: true,
    )
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
    )
    // Explicitly set PHP version for consistency
    ->withPhpVersion(80100); // PHP 8.1.0

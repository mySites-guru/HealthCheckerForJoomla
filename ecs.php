<?php

declare(strict_types=1);

/**
 * @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @link        https://github.com/mySites-guru/HealthCheckerForJoomla
 */

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/healthchecker', __DIR__ . '/tests', __DIR__ . '/rector.php', __DIR__ . '/ecs.php'])
    ->withSkip([
        // Skip language files
        '*/language/*',
        // Skip XML files
        '*.xml',
        // Skip media files
        '*/media/*',
        // Skip tmpl files (Joomla templates)
        '*/tmpl/*',
    ])
    ->withRules([
        ArraySyntaxFixer::class,
        NoUnusedImportsFixer::class,
        OrderedImportsFixer::class,
        ArrayIndentationFixer::class,
        BinaryOperatorSpacesFixer::class,
    ])
    ->withConfiguredRule(HeaderCommentFixer::class, [
        'header' => <<<'HEADER'
        @copyright   (C) 2026 https://mySites.guru + Phil E. Taylor <phil@phil-taylor.com>
        @license     GNU General Public License version 2 or later; see LICENSE.txt
        @link        https://github.com/mySites-guru/HealthCheckerForJoomla
        HEADER
        ,
        'comment_type' => 'PHPDoc',
        'location' => 'after_declare_strict',
        'separate' => 'both',
    ])
    ->withPreparedSets(
        psr12: true,
        arrays: true,
        comments: true,
        docblocks: true,
        spaces: true,
        namespaces: true,
        controlStructures: true,
        strict: true,
        symplify: true,
        cleanCode: true,
    )
    ->withPhpCsFixerSets(perCS20: true);

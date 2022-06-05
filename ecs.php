<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Squiz\Sniffs\Classes\ValidClassNameSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\ClassCommentSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\FileCommentSniff;
use PHP_CodeSniffer\Standards\Squiz\Sniffs\Commenting\FunctionCommentThrowTagSniff;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function (ECSConfig $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // run and fix, one by one
    $containerConfigurator->import('vendor/whatwedo/php-coding-standard/config/whatwedo-symfony.php');

    $containerConfigurator->parameters()->set(Option::SKIP, [
        FileCommentSniff::class,
        ClassCommentSniff::class,
        FunctionCommentThrowTagSniff::class,
        ValidClassNameSniff::class => [
            __DIR__ . '/src/whatwedoCrudBundle.php',
            __DIR__ . '/src/DependencyInjection/whatwedoCrudExtension.php',
        ],
        \PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer::class => [
            __DIR__ . '/tests/App/config/bundles.php',
        ],
        \PhpCsFixer\Fixer\Whitespace\ArrayIndentationFixer::class => [
            __DIR__ . '/tests/App/config/bundles.php',
        ],
        \Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer::class => [
            __DIR__ . '/tests/App/config/bundles.php',
        ],
        \Symplify\CodingStandard\Fixer\ArrayNotation\StandaloneLineInMultilineArrayFixer::class => [
            __DIR__ . '/tests/App/config/bundles.php',
        ],

        \Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer::class => [
            __DIR__ . '/tests/App/config/bundles.php',
        ],
    ]);

    $parameters->set(Option::PARALLEL, true);
};

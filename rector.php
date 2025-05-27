<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Laravel\Set\LaravelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/bootstrap',
        __DIR__ . '/storage',
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',
    ]);

    // Define what rule sets will be applied
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_84,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        LaravelSetList::LARAVEL_100,
    ]);

    // Register a single rule
    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // Auto import fully qualified class names
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);

    // Disable parallel processing to avoid potential issues
    $rectorConfig->parallel();
};

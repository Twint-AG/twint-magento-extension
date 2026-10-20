<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Api',
        __DIR__ . '/Block',
        __DIR__ . '/Builder',
        __DIR__ . '/Constant',
        __DIR__ . '/Controller',
        __DIR__ . '/Cron',
        __DIR__ . '/Exception',
        __DIR__ . '/Helper',
        __DIR__ . '/Logger',
        __DIR__ . '/Model',
        __DIR__ . '/Observer',
        __DIR__ . '/Plugin',
        __DIR__ . '/Provider',
        __DIR__ . '/Service',
        __DIR__ . '/Util',
        __DIR__ . '/Validator',
        __DIR__ . '/Setup',
    ])
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    // register single rule
    ->withRules([TypedPropertyFromStrictConstructorRector::class])
    // here we can define, what prepared sets of rules will be applied
    ->withPreparedSets(deadCode: true, codeQuality: true);

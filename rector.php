<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/config',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        earlyReturn: true,
        typeDeclarations: true,
    )
    ->withPhpSets();

<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Basic\PsrAutoloadingFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->import(__DIR__ . '/ecs.base.php');
    $ecsConfig->paths([__DIR__]);
    $ecsConfig->skip([__DIR__ . '/infra']);
    $ecsConfig->skip([__DIR__ . '/zinfra']);
    $ecsConfig->skip([__DIR__ . '/bin']);
    $ecsConfig->skip([__DIR__ . '/magento']);

    $ecsConfig->ruleWithConfiguration(PsrAutoloadingFixer::class, [
        'dir' => __DIR__,
    ]);

    $ecsConfig->cacheDirectory('/tmp/ecs/src');
};

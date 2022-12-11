<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Rector\MethodCall\GetParameterToConstructorInjectionRector;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Symfony\Set\SymfonyLevelSetList;
use Rector\Symfony\Set\SymfonySetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/config',
        __DIR__ . '/legacy',
        __DIR__ . '/migrations',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->parallel(300, 8, 4);
//    $rectorConfig->disableParallel();

    $rectorConfig->symfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml');
    $rectorConfig->symfonyContainerPhp(__DIR__ . '/tests/symfony-container.php');

    // register a single rule
//    $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_80,
        SensiolabsSetList::FRAMEWORK_EXTRA_50,
        SensiolabsSetList::FRAMEWORK_EXTRA_61,
        SymfonyLevelSetList::UP_TO_SYMFONY_54,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
    ]);

    $rectorConfig->skip([
        GetParameterToConstructorInjectionRector::class,
    ]);
};

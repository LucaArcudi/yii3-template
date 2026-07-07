<?php

declare(strict_types=1);

use App\Shared\Helpers\Translate;
use Psr\Container\ContainerInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @psalm-var list<callable(ContainerInterface): void>
 */
return [
    static function (ContainerInterface $container): void {
        Translate::setTranslator($container->get(TranslatorInterface::class));
    },
];

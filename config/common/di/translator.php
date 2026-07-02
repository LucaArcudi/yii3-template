<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\SimpleMessageFormatter;

// La definizione di TranslatorInterface arriva dal pacchetto yiisoft/translator
// (config vendor) e raccoglie tutte le CategorySource taggate 'translation.categorySource'.
$formatter = static fn(): IntlMessageFormatter|SimpleMessageFormatter => extension_loaded('intl')
    ? new IntlMessageFormatter()
    : new SimpleMessageFormatter();

return [
    // Messaggi dell'applicazione: ID in italiano, traduzioni in @messages/{locale}/app.php.
    'translation.app' => [
        'definition' => static fn(Aliases $aliases): CategorySource => new CategorySource(
            'app',
            new MessageSource($aliases->get('@messages')),
            $formatter(),
        ),
        'tags' => ['translation.categorySource'],
    ],

    // Traduzioni italiane dei messaggi del validator Yii (il pacchetto non le fornisce):
    // @messages/it/yii-validator.php. Per le altre lingue restano i messaggi del vendor.
    'translation.validator' => [
        'definition' => static fn(Aliases $aliases): CategorySource => new CategorySource(
            'yii-validator',
            new MessageSource($aliases->get('@messages')),
            $formatter(),
        ),
        'tags' => ['translation.categorySource'],
    ],
];

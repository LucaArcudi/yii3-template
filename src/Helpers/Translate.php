<?php

declare(strict_types=1);

namespace App\Helpers;

use Yiisoft\Translator\SimpleMessageFormatter;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Ponte statico verso il translator per i contesti senza DI (widget, presenter, viste).
 * Viene inizializzato in config/common/bootstrap.php; senza container (es. unit test)
 * restituisce il messaggio sorgente in italiano formattando i parametri.
 */
final class Translate
{
    private static ?TranslatorInterface $translator = null;

    public static function setTranslator(?TranslatorInterface $translator): void
    {
        self::$translator = $translator;
    }

    /**
     * Traduce un messaggio della categoria "app" nella lingua corrente.
     *
     * @param string $id Testo sorgente in italiano, con placeholder in formato {nome}.
     * @param array $parameters Valori per i placeholder.
     */
    public static function t(string $id, array $parameters = [], ?string $category = null): string
    {
        if (self::$translator === null) {
            return $parameters === []
                ? $id
                : (new SimpleMessageFormatter())->format($id, $parameters, AppLocales::DEFAULT);
        }

        return self::$translator->translate($id, $parameters, $category);
    }

    public static function locale(): string
    {
        return self::$translator?->getLocale() ?? AppLocales::DEFAULT;
    }
}

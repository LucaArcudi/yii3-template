<?php

declare(strict_types=1);

namespace App\Widgets;

use App\Helpers\Translate;
use Stringable;
use Yiisoft\Html\Html;
use Yiisoft\Session\Flash\FlashInterface;

use function is_array;
use function ucfirst;

final class FlashMessages
{
    public static function render(FlashInterface $flash): string
    {
        $flashes = $flash->getAll();

        if ($flashes === []) {
            return '';
        }

        $items = [];

        foreach ($flashes as $type => $messages) {
            [$variant, $icon] = self::resolvePresentation($type);

            foreach (self::normalizeMessages($messages, $type) as $message) {
                $items[] = (string) Html::div(
                    (string) Html::div(
                        (string) Html::i('', ['class' => $icon]),
                        ['class' => 'app-flash__icon'],
                    )->encode(false)
                    . (string) Html::div(
                        (string) Html::div(self::resolveTitle($type), ['class' => 'app-flash__title'])
                        . (string) Html::div($message, ['class' => 'app-flash__text']),
                        ['class' => 'app-flash__content'],
                    )->encode(false),
                    [
                        'class' => ['alert', 'alert-' . $variant, 'app-flash', 'mb-0'],
                        'role' => 'alert',
                    ],
                )->encode(false);
            }
        }

        return (string) Html::div(
            implode('', $items),
            ['class' => 'app-flash-stack mb-4'],
        )->encode(false);
    }

    /**
     * @return array{string, string}
     */
    private static function resolvePresentation(string $type): array
    {
        return match ($type) {
            'success' => ['success', 'fa-solid fa-circle-check'],
            'warning' => ['warning', 'fa-solid fa-triangle-exclamation'],
            'danger', 'error' => ['danger', 'fa-solid fa-circle-exclamation'],
            'info' => ['info', 'fa-solid fa-circle-info'],
            default => ['primary', 'fa-solid fa-bell'],
        };
    }

    /**
     * @return list<string>
     */
    private static function normalizeMessages(mixed $messages, string $type): array
    {
        if (is_array($messages)) {
            $normalized = [];

            foreach ($messages as $message) {
                $normalized[] = self::normalizeMessage($message, $type);
            }

            return $normalized;
        }

        return [self::normalizeMessage($messages, $type)];
    }

    private static function normalizeMessage(mixed $message, string $type): string
    {
        return match (true) {
            $message instanceof Stringable => (string) $message,
            $message === true => self::resolveTitle($type) . '.',
            $message === false || $message === null => self::resolveTitle($type) . '.',
            default => (string) $message,
        };
    }

    private static function resolveTitle(string $type): string
    {
        return match ($type) {
            'success' => Translate::t('Operazione completata'),
            'warning' => Translate::t('Attenzione'),
            'danger', 'error' => Translate::t('Si è verificato un problema'),
            'info' => Translate::t('Informazione'),
            default => Translate::t('Messaggio {type}', ['type' => ucfirst($type)]),
        };
    }

    private function __construct() {}
}

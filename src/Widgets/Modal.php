<?php

declare(strict_types=1);

namespace App\Widgets;

use Yiisoft\Html\Html;

final class Modal
{
    public static function render(
        string $id,
        string $title,
        string $body,
        ?string $footer = null,
        string $variant = 'primary',
        ?string $icon = null,
        string $size = 'md',
        bool $centered = false,
        bool $scrollable = false,
    ): string {
        $variant = self::normalizeVariant($variant);

        $dialogClasses = ['modal-dialog'];

        if ($centered) {
            $dialogClasses[] = 'modal-dialog-centered';
        }

        if ($scrollable) {
            $dialogClasses[] = 'modal-dialog-scrollable';
        }

        $sizeClass = match ($size) {
            'sm' => 'modal-sm',
            'lg' => 'modal-lg',
            'xl' => 'modal-xl',
            default => null,
        };

        if ($sizeClass !== null) {
            $dialogClasses[] = $sizeClass;
        }

        $titleContent = '';

        if ($icon !== null && $icon !== '') {
            $titleContent .= (string) Html::span(
                (string) Html::i('', ['class' => $icon]),
                ['class' => 'app-modal__icon'],
            )->encode(false);
        }

        $titleContent .= (string) Html::div(
            (string) Html::h5($title, [
                'class' => 'modal-title app-modal__title',
                'id' => $id . 'Label',
            ]),
            ['class' => 'app-modal__title-wrap'],
        )->encode(false);

        $header = (string) Html::div(
            $titleContent
            . (string) Html::button('', [
                'type' => 'button',
                'class' => 'btn-close',
                'data-bs-dismiss' => 'modal',
                'aria-label' => 'Chiudi',
            ]),
            ['class' => ['modal-header', 'app-modal__header']],
        )->encode(false);

        $sections = [
            $header,
            (string) Html::div($body, ['class' => ['modal-body', 'app-modal__body']])->encode(false),
        ];

        if ($footer !== null && $footer !== '') {
            $sections[] = (string) Html::div($footer, ['class' => ['modal-footer', 'app-modal__footer']])->encode(false);
        }

        return (string) Html::div(
            (string) Html::div(
                (string) Html::div(
                    implode('', $sections),
                    ['class' => ['modal-content', 'app-modal__content', 'app-modal__content--' . $variant]],
                )->encode(false),
                ['class' => $dialogClasses],
            )->encode(false),
            [
                'class' => ['modal', 'fade', 'app-modal'],
                'id' => $id,
                'tabindex' => '-1',
                'role' => 'dialog',
                'aria-modal' => 'true',
                'aria-labelledby' => $id . 'Label',
                'aria-hidden' => 'true',
            ],
        )->encode(false);
    }

    private static function normalizeVariant(string $variant): string
    {
        return match ($variant) {
            'primary', 'secondary', 'success', 'info', 'warning', 'danger', 'light', 'dark' => $variant,
            default => 'primary',
        };
    }

    private function __construct() {}
}

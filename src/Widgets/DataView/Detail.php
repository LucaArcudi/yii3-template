<?php

declare(strict_types=1);

namespace App\Widgets\DataView;

use App\Widgets\Card;
use Closure;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\DetailView\DataField;
use Yiisoft\Yii\DataView\DetailView\DetailView;

class Detail
{
    public static function htmlField(
        string $property,
        ?string $label = null,
        mixed $value = null,
        array|Closure $valueAttributes = [],
        array|Closure $fieldAttributes = [],
        bool $visible = true,
    ): DataField {
        return new DataField(
            property: $property,
            label: $label,
            value: $value,
            valueEncode: false,
            valueAttributes: $valueAttributes,
            fieldAttributes: $fieldAttributes,
            visible: $visible,
        );
    }

    public static function render(
        string $title,
        array $data,
        array $fields,
        ?string $before = null,
        ?string $after = null,
        string $variant = 'primary',
    ): string {
        $body = [];

        if ($before !== null && $before !== '') {
            $body[] = $before;
        }

        $body[] = (string) Html::div(
            (string) DetailView::widget()
                ->data($data)
                ->fields(...$fields),
            ['class' => 'app-detail-wrapper'],
        )->encode(false);

        if ($after !== null && $after !== '') {
            $body[] = $after;
        }

        return Card::render(
            title: $title,
            body: implode('', $body),
            variant: $variant,
            icon: 'pe-7s-note2',
        );
    }

    private function __construct() {}
}

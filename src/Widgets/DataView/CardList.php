<?php

declare(strict_types=1);

namespace App\Widgets\DataView;

use App\Helpers\Translate;
use App\Widgets\Card;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Html\Html;

use function max;
use function min;

final class CardList
{
    public static function render(
        string $title,
        DataReaderInterface $reader,
        callable $itemRenderer,
        callable $urlCreator,
        int $page = 1,
        int $pageSize = 5,
        ?string $toolbar = null,
        string $variant = 'info',
        ?string $emptyText = null,
    ): string {
        $emptyText ??= Translate::t('Nessun elemento trovato con i filtri correnti.');
        $total = $reader->count();
        $totalPages = max(1, (int) ceil($total / $pageSize));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $pageSize;
        $rows = iterator_to_array($reader->withLimit($pageSize)->withOffset($offset)->read());
        $items = [];

        foreach ($rows as $row) {
            $items[] = (string) $itemRenderer($row);
        }

        $body = $items === []
            ? (string) Html::div($emptyText, ['class' => 'app-card-list__empty'])->encode(false)
            : (string) Html::div(implode('', $items), ['class' => 'app-card-list__grid'])->encode(false);

        $pagination = Pagination::render($total, $page, $pageSize, $urlCreator);
        $footerParts = [];

        if ($total > 0) {
            $begin = $offset + 1;
            $end = min($offset + $pageSize, $total);
            $footerParts[] = (string) Html::div(
                Translate::t('Visualizzando {range} di {total}', [
                    'range' => '<strong>' . $begin . '-' . $end . '</strong>',
                    'total' => '<strong>' . $total . '</strong>',
                ]),
                ['class' => ['app-admin-grid__summary', 'app-card-list__summary', 'me-auto']],
            )->encode(false);
        }

        if ($pagination !== '') {
            $footerParts[] = (string) Html::div(
                $pagination,
                ['class' => ['app-card-list__pagination', 'ms-auto']],
            )->encode(false);
        }

        $footer = $footerParts === []
            ? null
            : (string) Html::div(
                implode('', $footerParts),
                ['class' => ['app-admin-grid__meta', 'app-card-list__footer', 'w-100']],
            )->encode(false);

        return Card::render(
            title: $title,
            body: $body,
            variant: $variant,
            tools: $toolbar,
            footer: $footer,
            icon: 'fa-solid fa-layer-group',
        );
    }

    private function __construct() {}
}

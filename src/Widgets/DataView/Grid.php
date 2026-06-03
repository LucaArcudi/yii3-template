<?php

declare(strict_types=1);

namespace App\Widgets\DataView;

use App\Widgets\Card;
use Yiisoft\Data\Reader\DataReaderInterface;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;

class Grid
{
    public static function render(
        string $title,
        DataReaderInterface $reader,
        array $columns,
        ?string $toolbar = null,
        string $variant = 'primary',
        ?callable $urlCreator = null,
        ?array $queryParameters = null,
    ): string {
        $layout = (string) Html::div('{items}', ['class' => ['table-responsive', 'app-grid-wrapper']])->encode(false)
            . "\n"
            . (string) Html::div('{summary}{pager}', ['class' => 'app-admin-grid__meta'])->encode(false);

        $body = (string) Html::div(
            (string) GridView::widget()
            ->dataReader($reader)
            ->columns(...$columns)
            ->urlCreator($urlCreator)
            ->urlParameterProvider(new QueryStringParameterProvider($queryParameters))
            ->containerAttributes(['class' => 'app-admin-grid'])
            ->tableClass('table', 'table-hover', 'align-middle', 'mb-0', 'app-admin-grid__table')
            ->headerRowAttributes(['class' => 'app-admin-grid__header'])
            ->filterCellAttributes(['class' => 'app-admin-grid__filter-cell'])
            ->filterFormAttributes(['class' => 'app-admin-grid__filter-form'])
            ->bodyRowAttributes(static fn(array|object $row, object $context): array => ['class' => 'app-admin-grid__row'])
            ->noResultsText('Nessun elemento trovato con i filtri correnti.')
            ->noResultsCellAttributes(['class' => 'app-admin-grid__empty'])
            ->summaryAttributes(['class' => 'app-admin-grid__summary'])
            ->summaryTemplate('<span class="app-admin-grid__summary-range">Visualizzando <strong>{begin}-{end}</strong>  di <strong>{totalCount}</strong></span>')
            ->sortableLinkAttributes(['class' => 'app-admin-grid__sort-link'])
            ->sortableHeaderAppend(' <i class="fa-solid fa-sort text-black-50"></i>')
            ->sortableHeaderAscAppend(' <i class="fa-solid fa-sort-up"></i>')
            ->sortableHeaderDescAppend(' <i class="fa-solid fa-sort-down"></i>')
            ->paginationWidget(
                OffsetPagination::widget()
                    ->containerAttributes(['class' => 'app-admin-grid__pager'])
                    ->listTag('ul')
                    ->listAttributes(['class' => 'pagination pagination-sm mb-0'])
                    ->itemTag('li')
                    ->itemAttributes(['class' => 'page-item'])
                    ->currentItemClass('active')
                    ->disabledItemClass('disabled')
                    ->linkClass('page-link')
                    ->currentLinkClass('page-link')
                    ->disabledLinkClass('page-link'),
            )
            ->pageSizeConstraint([5])
            ->pageSizeTemplate(null)
            ->layout($layout),
            ['class' => 'app-admin-grid-shell'],
        )->encode(false);

        return Card::render(
            title: $title,
            body: $body,
            variant: $variant,
            tools: $toolbar,
            icon: 'pe-7s-network',
        );
    }

    private function __construct() {}
}

<?php

declare(strict_types=1);

namespace App\Shared\Widgets\DataView;

use App\Shared\Helpers\Translate;
use Yiisoft\Html\Html;

use function max;
use function min;

final class Pagination
{
    public static function render(
        int $totalItems,
        int $currentPage,
        int $pageSize,
        callable $urlCreator,
    ): string {
        $totalPages = max(1, (int) ceil($totalItems / $pageSize));

        if ($totalPages <= 1) {
            return '';
        }

        $currentPage = min(max(1, $currentPage), $totalPages);
        $items = [];
        $items[] = self::item(Translate::t('Prima'), 1, $currentPage === 1, false, $urlCreator);
        $items[] = self::item(Translate::t('Prec.'), max(1, $currentPage - 1), $currentPage === 1, false, $urlCreator);

        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);

        for ($page = $start; $page <= $end; $page++) {
            $items[] = self::item((string) $page, $page, false, $page === $currentPage, $urlCreator);
        }

        $items[] = self::item(Translate::t('Succ.'), min($totalPages, $currentPage + 1), $currentPage === $totalPages, false, $urlCreator);
        $items[] = self::item(Translate::t('Ultima'), $totalPages, $currentPage === $totalPages, false, $urlCreator);

        return (string) Html::tag(
            'nav',
            (string) Html::tag('ul', implode('', $items), ['class' => 'pagination pagination-sm mb-0'])->encode(false),
            ['class' => 'app-admin-grid__pager', 'aria-label' => Translate::t('Paginazione')],
        )->encode(false);
    }

    private static function item(
        string $label,
        int $page,
        bool $disabled,
        bool $active,
        callable $urlCreator,
    ): string {
        $link = (string) Html::a(
            $label,
            $disabled ? '#' : (string) $urlCreator($page),
            [
                'class' => ['page-link'],
                'tabindex' => $disabled ? '-1' : null,
                'aria-disabled' => $disabled ? 'true' : null,
            ],
        );

        return (string) Html::tag(
            'li',
            $link,
            ['class' => ['page-item', $disabled ? 'disabled' : null, $active ? 'active' : null]],
        )->encode(false);
    }

    private function __construct() {}
}

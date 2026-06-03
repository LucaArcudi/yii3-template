<?php

declare(strict_types=1);

use App\Data\Core\Notification\NotificationPresenter;
use App\Widgets\DataView\Grid;
use Yiisoft\Data\Db\QueryDataReader;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;

/** @var QueryDataReader $reader */
/** @var callable $gridUrlCreator */

$this->setTitle('Notifiche');
$this->setParameter('pageIcon', 'pe-7s-bell');
$this->setParameter('breadcrumbs', [
    ['label' => 'Dashboard', 'url' => '/'],
    ['label' => 'Notifiche'],
]);

echo Grid::render(
    title: 'Centro notifiche',
    reader: $reader,
    variant: 'info',
    urlCreator: $gridUrlCreator,
    columns: [
        new DataColumn(
            property: 'title',
            header: 'Notifica',
            bodyAttributes: ['class' => 'text-wrap'],
            content: static function (array|object $row): string {
                $notification = new NotificationPresenter($row);
                $description = $notification->description();

                return '<div class="app-notification-row">'
                    . '<div class="app-notification-row__title">' . htmlspecialchars($notification->title(), ENT_QUOTES, 'UTF-8') . '</div>'
                    . ($description !== ''
                        ? '<div class="app-notification-row__text">' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '</div>'
                        : '')
                    . '</div>';
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'is_read',
            header: 'Stato',
            content: static fn (array|object $row): string => (new NotificationPresenter($row))->statusBadge(),
            encodeContent: false,
        ),
        new DataColumn(
            property: 'created_at',
            header: 'Data',
            content: static fn (array|object $row): string => (new NotificationPresenter($row))->createdAt(),
        ),
        new DataColumn(
            header: 'Risorsa',
            withSorting: false,
            content: static function (array|object $row): string {
                $notification = new NotificationPresenter($row);

                return (string) Html::a(
                    (string) Html::i('', ['class' => 'fa-solid fa-arrow-up-right-from-square me-1']) . 'Apri',
                    '/notification/open/' . $notification->id(),
                    ['class' => ['btn', 'btn-sm', 'btn-outline-primary']],
                )->encode(false);
            },
            encodeContent: false,
        ),
    ],
);

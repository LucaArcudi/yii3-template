<?php

declare(strict_types=1);

use App\Core\Notification\NotificationPresenter;
use App\Helpers\Translate;
use App\Widgets\DataView\Grid;
use Yiisoft\Data\Db\QueryDataReader;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;

/** @var QueryDataReader $reader */
/** @var callable $gridUrlCreator */

$this->setTitle(Translate::t('Notifiche'));
$this->setParameter('pageIcon', 'pe-7s-bell');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Notifiche')],
]);

echo Grid::render(
    title: Translate::t('Centro notifiche'),
    reader: $reader,
    variant: 'info',
    urlCreator: $gridUrlCreator,
    columns: [
        new DataColumn(
            property: 'title',
            header: Translate::t('Notifica'),
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
            header: Translate::t('Stato'),
            content: static fn(array|object $row): string => (new NotificationPresenter($row))->statusBadge(),
            encodeContent: false,
        ),
        new DataColumn(
            property: 'created_at',
            header: Translate::t('Data'),
            content: static fn(array|object $row): string => (new NotificationPresenter($row))->createdAt(),
        ),
        new DataColumn(
            header: Translate::t('Risorsa'),
            withSorting: false,
            content: static function (array|object $row): string {
                $notification = new NotificationPresenter($row);

                return (string) Html::a(
                    (string) Html::i('', ['class' => 'fa-solid fa-arrow-up-right-from-square me-1']) . Html::encode(Translate::t('Apri')),
                    '/notification/open/' . $notification->id(),
                    ['class' => ['btn', 'btn-sm', 'btn-outline-primary']],
                )->encode(false);
            },
            encodeContent: false,
        ),
    ],
);

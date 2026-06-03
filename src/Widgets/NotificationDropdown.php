<?php

declare(strict_types=1);

namespace App\Widgets;

use App\Data\Core\Notification\NotificationPresenter;
use App\Data\Core\Notification\NotificationReader;
use Yiisoft\Html\Html;
use Yiisoft\Yii\View\Renderer\Csrf;

final class NotificationDropdown
{
    public static function render(NotificationReader $reader, Csrf $csrf, bool $canAccessIndex = false): string
    {
        $unreadCount = $reader->unreadCountForCurrentUser();
        $items = $reader->recentForCurrentUser(5);
        $badge = $unreadCount > 0
            ? (string) Html::span((string) min($unreadCount, 99), ['class' => 'app-notification-menu__badge'])
            : '';

        $list = $items === []
            ? (string) Html::div('Nessuna notifica recente.', ['class' => 'app-notification-menu__empty'])
            : implode('', array_map(static function (array $row): string {
                $notification = new NotificationPresenter($row);
                $description = $notification->description();
                $isUnread = !$notification->isRead();
                $title = (string) Html::span($notification->title(), ['class' => 'app-notification-menu__item-title']);

                if ($isUnread) {
                    $title .= (string) Html::span('Da leggere', ['class' => 'app-notification-menu__unread-label']);
                }

                return (string) Html::a(
                    (string) Html::span(
                        (string) Html::i('', ['class' => 'pe-7s-bell']),
                        ['class' => 'app-notification-menu__item-icon'],
                    )->encode(false)
                    . (string) Html::span(
                        (string) Html::span($title, ['class' => 'app-notification-menu__item-heading'])->encode(false)
                        . ($description !== ''
                            ? (string) Html::span($description, ['class' => 'app-notification-menu__item-text'])
                            : '')
                        . (string) Html::span($notification->createdAt(), ['class' => 'app-notification-menu__item-date']),
                        ['class' => 'app-notification-menu__item-copy'],
                    )->encode(false),
                    '/notification/open/' . $notification->id(),
                    ['class' => ['dropdown-item', 'app-notification-menu__item', $isUnread ? 'is-unread' : null]],
                )->encode(false);
            }, $items));

        $readAll = $unreadCount > 0
            ? '<form method="post" action="/notification/read-all" class="m-0">'
                . $csrf->hiddenInput()
                . (string) Html::button('Segna tutte come lette', ['type' => 'submit', 'class' => 'dropdown-item app-notification-menu__footer-action'])
                . '</form>'
            : '';
        $indexLink = $canAccessIndex
            ? (string) Html::a('Vedi tutte', '/notification', ['class' => 'dropdown-item app-notification-menu__footer-action'])
            : '';
        $footerContent = $readAll . $indexLink;
        $footer = $footerContent !== ''
            ? (string) Html::div($footerContent, ['class' => 'app-notification-menu__footer'])->encode(false)
            : '';

        return (string) Html::div(
            (string) Html::button(
                (string) Html::i('', ['class' => 'pe-7s-bell'])
                . $badge
                . (string) Html::span('Notifiche', ['class' => 'visually-hidden']),
                [
                    'type' => 'button',
                    'class' => ['btn', 'app-notification-menu__toggle'],
                    'data-bs-toggle' => 'dropdown',
                    'aria-haspopup' => 'true',
                    'aria-expanded' => 'false',
                ],
            )->encode(false)
            . (string) Html::div(
                (string) Html::div(
                    (string) Html::div(
                        (string) Html::div('Notifiche', ['class' => 'menu-header-title'])
                        . (string) Html::div($unreadCount . ' non lette', ['class' => 'menu-header-subtitle']),
                        ['class' => 'dropdown-menu-header-inner bg-mean-fruit'],
                    )->encode(false),
                    ['class' => 'dropdown-menu-header'],
                )->encode(false)
                . (string) Html::div($list, ['class' => 'app-notification-menu__list'])->encode(false)
                . $footer,
                ['class' => ['dropdown-menu', 'dropdown-menu-end', 'dropdown-menu-lg', 'app-notification-menu__dropdown']],
            )->encode(false),
            ['class' => ['btn-group', 'app-notification-menu']],
        )->encode(false);
    }

    private function __construct()
    {
    }
}

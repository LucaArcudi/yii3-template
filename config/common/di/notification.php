<?php

declare(strict_types=1);

use App\Data\Core\Notification\NotificationReader;
use App\Data\Core\Notification\NotificationRepository;

return [
    NotificationReader::class => NotificationReader::class,
    NotificationRepository::class => NotificationRepository::class,
];

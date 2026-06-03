<?php

declare(strict_types=1);

namespace App\Data\Core\Notification;

use App\Data\Core\BaseEntity;

final class NotificationEntity extends BaseEntity
{
    public function __construct(
        public ?int $id = null,
        public string $title = '',
        public ?string $description = null,
        public ?string $url = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?int $createdBy = null,
        ?int $updatedBy = null,
    ) {
        parent::__construct($createdAt, $updatedAt, $createdBy, $updatedBy);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}

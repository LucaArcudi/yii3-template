<?php

declare(strict_types=1);

namespace App\Shared\Data;

use function date;

abstract class BaseEntity
{
    public function __construct(
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?int $createdBy = null,
        public ?int $updatedBy = null,
    ) {}

    public function stampCreated(?int $actorId, ?string $dateTime = null): void
    {
        $dateTime ??= date('Y-m-d H:i:s');

        $this->createdAt ??= $dateTime;
        $this->updatedAt ??= $dateTime;
        $this->createdBy ??= $actorId;
        $this->updatedBy ??= $actorId;
    }

    public function stampUpdated(?int $actorId, ?string $dateTime = null): void
    {
        $this->updatedAt = $dateTime ?? date('Y-m-d H:i:s');
        $this->updatedBy = $actorId;
    }

    public function auditArray(): array
    {
        return [
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}

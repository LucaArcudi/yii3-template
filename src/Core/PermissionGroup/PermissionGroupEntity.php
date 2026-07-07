<?php

declare(strict_types=1);

namespace App\Core\PermissionGroup;

use App\Data\Core\BaseEntity;

final class PermissionGroupEntity extends BaseEntity
{
    public function __construct(
        public ?int $id = null,
        public string $name = '',
        public string $code = '',
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
            'name' => $this->name,
            'code' => $this->code,
            ...$this->auditArray(),
        ];
    }
}

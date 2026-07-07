<?php

declare(strict_types=1);

namespace App\Core\Permission;

use App\Shared\Data\BaseEntity;

final class PermissionEntity extends BaseEntity
{
    public function __construct(
        public ?int $id = null,
        public ?int $groupId = null,
        public string $name = '',
        public string $code = '',
        public int $weight = 1,
        public ?string $groupName = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?int $createdBy = null,
        ?int $updatedBy = null,
    ) {
        parent::__construct($createdAt, $updatedAt, $createdBy, $updatedBy);
    }

    public function rename(string $name): void
    {
        $this->name = trim($name);
    }

    public function changeCode(string $code): void
    {
        $this->code = trim($code);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'groupId' => $this->groupId,
            'groupName' => $this->groupName,
            'name' => $this->name,
            'code' => $this->code,
            'weight' => $this->weight,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}

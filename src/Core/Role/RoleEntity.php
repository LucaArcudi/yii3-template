<?php

declare(strict_types=1);

namespace App\Core\Role;

use App\Shared\Data\BaseEntity;

final class RoleEntity extends BaseEntity
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
            'name' => $this->name,
            'code' => $this->code,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}

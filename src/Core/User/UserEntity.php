<?php

declare(strict_types=1);

namespace App\Core\User;

use App\Data\Core\BaseEntity;
use App\Helpers\Translate;

use function date;
use function strtotime;

final class UserEntity extends BaseEntity
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_INACTIVE = 0;

    public function __construct(
        public ?int $id = null,
        public string $email = '',
        public string $passwordHash = '',
        public string $name = '',
        public int $status = self::STATUS_ACTIVE,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        public ?string $lastLoginAt = null,
        public ?string $rememberTokenHash = null,
        public ?string $passwordChangedAt = null,
        public ?string $passwordExpiresAt = null,
        public ?string $passwordResetSelector = null,
        public ?string $passwordResetTokenHash = null,
        public ?string $passwordResetTokenExpiresAt = null,
        ?int $createdBy = null,
        ?int $updatedBy = null,
    ) {
        parent::__construct($createdAt, $updatedAt, $createdBy, $updatedBy);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPasswordExpired(?string $now = null): bool
    {
        if ($this->passwordExpiresAt === null || $this->passwordExpiresAt === '') {
            return false;
        }

        return strtotime($this->passwordExpiresAt) <= strtotime($now ?? date('Y-m-d H:i:s'));
    }

    public function isPasswordResetTokenExpired(?string $now = null): bool
    {
        if ($this->passwordResetTokenExpiresAt === null || $this->passwordResetTokenExpiresAt === '') {
            return true;
        }

        return strtotime($this->passwordResetTokenExpiresAt) < strtotime($now ?? date('Y-m-d H:i:s'));
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => Translate::t('Attivo'),
            self::STATUS_INACTIVE => Translate::t('Inattivo'),
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? Translate::t('Sconosciuto');
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'lastLoginAt' => $this->lastLoginAt,
            'passwordChangedAt' => $this->passwordChangedAt,
            'passwordExpiresAt' => $this->passwordExpiresAt,
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}

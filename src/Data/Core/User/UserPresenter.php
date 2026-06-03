<?php

declare(strict_types=1);

namespace App\Data\Core\User;

use App\Widgets\Badge;

final readonly class UserPresenter
{
    public function __construct(
        private UserEntity|array $data,
    ) {
    }

    private function get(string $key, mixed $default = null): mixed
    {
        if ($this->data instanceof UserEntity) {
            return $this->data->{$key} ?? $default;
        }

        if (is_array($this->data)) {
            return $this->data[$key] ?? $default;
        }

        return $this->data->{$key} ?? $default;
    }

    public function id(): ?int
    {
        $value = $this->get('id');

        return $value !== null ? (int) $value : null;
    }

    public function email(): string
    {
        return (string) $this->get('email', '');
    }

    public function name(): string
    {
        return (string) $this->get('name', '');
    }

    public function status(): int
    {
        return (int) $this->get('status', UserEntity::STATUS_ACTIVE);
    }

    public function statusLabel(): string
    {
        return UserEntity::statusOptions()[$this->status()] ?? 'Sconosciuto';
    }

    public function statusVariant(): string
    {
        return match ($this->status()) {
            UserEntity::STATUS_ACTIVE => 'success',
            UserEntity::STATUS_INACTIVE => 'secondary',
            default => 'dark',
        };
    }

    public function statusBadge(): string
    {
        return Badge::render($this->statusLabel(), $this->statusVariant());
    }

    public function createdAt(): string
    {
        $value = $this->get('created_at', $this->get('createdAt'));

        if ($value === null || $value === '') {
            return '-';
        }

        return date('d/m/Y H:i', strtotime((string) $value));
    }

    public function updatedAt(): string
    {
        $value = $this->get('updated_at', $this->get('updatedAt'));

        if ($value === null || $value === '') {
            return '-';
        }

        return date('d/m/Y H:i', strtotime((string) $value));
    }

    public function lastLoginAt(): string
    {
        $value = $this->get('last_login_at', $this->get('lastLoginAt'));

        if ($value === null || $value === '') {
            return '-';
        }

        return date('d/m/Y H:i', strtotime((string) $value));
    }

    public function passwordChangedAt(): string
    {
        $value = $this->get('password_changed_at', $this->get('passwordChangedAt'));

        if ($value === null || $value === '') {
            return '-';
        }

        return date('d/m/Y H:i', strtotime((string) $value));
    }

    public function passwordExpiresAt(): string
    {
        $value = $this->get('password_expires_at', $this->get('passwordExpiresAt'));

        if ($value === null || $value === '') {
            return 'Mai';
        }

        return date('d/m/Y H:i', strtotime((string) $value));
    }

    public function createdBy(): string
    {
        $value = $this->get('created_by', $this->get('createdBy'));

        if ($value === null || $value === '') {
            return '-';
        }

        return '#' . (int) $value;
    }

    public function updatedBy(): string
    {
        $value = $this->get('updated_by', $this->get('updatedBy'));

        if ($value === null || $value === '') {
            return '-';
        }

        return '#' . (int) $value;
    }

    public function toDetailArray(): array
    {
        return [
            'id' => $this->id(),
            'email' => $this->email(),
            'name' => $this->name(),
            'statusLabel' => $this->statusBadge(),
            'createdAt' => $this->createdAt(),
            'updatedAt' => $this->updatedAt(),
            'lastLoginAt' => $this->lastLoginAt(),
            'passwordChangedAt' => $this->passwordChangedAt(),
            'passwordExpiresAt' => $this->passwordExpiresAt(),
            'createdBy' => $this->createdBy(),
            'updatedBy' => $this->updatedBy(),
        ];
    }
}

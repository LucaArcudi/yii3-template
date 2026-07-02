<?php

declare(strict_types=1);

namespace App\Data\Core\Permission;

final readonly class PermissionPresenter
{
    public function __construct(
        private PermissionEntity|array $data,
    ) {}

    private function get(string $key, mixed $default = null): mixed
    {
        if ($this->data instanceof PermissionEntity) {
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

    public function name(): string
    {
        return (string) $this->get('name', '');
    }

    public function code(): string
    {
        return (string) $this->get('code', '');
    }

    public function weight(): int
    {
        return (int) $this->get('weight', 1);
    }

    public function groupName(): string
    {
        $value = $this->get('group_name', $this->get('groupName'));

        return $value !== null && $value !== '' ? (string) $value : '-';
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
            'groupName' => $this->groupName(),
            'name' => $this->name(),
            'code' => $this->code(),
            'weight' => $this->weight(),
            'createdAt' => $this->createdAt(),
            'updatedAt' => $this->updatedAt(),
            'createdBy' => $this->createdBy(),
            'updatedBy' => $this->updatedBy(),
        ];
    }
}

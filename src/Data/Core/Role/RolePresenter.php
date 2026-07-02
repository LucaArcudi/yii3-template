<?php

declare(strict_types=1);

namespace App\Data\Core\Role;

final readonly class RolePresenter
{
    public function __construct(
        private RoleEntity|array $data,
    ) {}

    private function get(string $key, mixed $default = null): mixed
    {
        if ($this->data instanceof RoleEntity) {
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
            'name' => $this->name(),
            'code' => $this->code(),
            'createdAt' => $this->createdAt(),
            'updatedAt' => $this->updatedAt(),
            'createdBy' => $this->createdBy(),
            'updatedBy' => $this->updatedBy(),
        ];
    }
}

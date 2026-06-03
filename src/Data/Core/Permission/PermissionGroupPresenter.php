<?php

declare(strict_types=1);

namespace App\Data\Core\Permission;

use function date;
use function strtotime;

final readonly class PermissionGroupPresenter
{
    public function __construct(
        private PermissionGroupEntity|array $data,
    ) {
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
        return $this->formatDate($this->get('created_at', $this->get('createdAt')));
    }

    public function updatedAt(): string
    {
        return $this->formatDate($this->get('updated_at', $this->get('updatedAt')));
    }

    public function createdBy(): string
    {
        return $this->userId('created_by', 'createdBy');
    }

    public function updatedBy(): string
    {
        return $this->userId('updated_by', 'updatedBy');
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

    private function get(string $key, mixed $default = null): mixed
    {
        if ($this->data instanceof PermissionGroupEntity) {
            return $this->data->{$key} ?? $default;
        }

        return $this->data[$key] ?? $default;
    }

    private function formatDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return date('d/m/Y H:i', strtotime((string) $value));
    }

    private function userId(string $snakeKey, string $camelKey): string
    {
        $value = $this->get($snakeKey, $this->get($camelKey));

        return $value === null || $value === '' ? '-' : '#' . (int) $value;
    }
}

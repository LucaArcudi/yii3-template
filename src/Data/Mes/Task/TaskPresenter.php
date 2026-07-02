<?php

declare(strict_types=1);

namespace App\Data\Mes\Task;

use App\Widgets\Badge;

final readonly class TaskPresenter
{
    public function __construct(
        private TaskEntity|array $data,
    ) {}

    private function get(string $key, mixed $default = null): mixed
    {
        if ($this->data instanceof TaskEntity) {
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

    public function title(): string
    {
        return (string) $this->get('title', '');
    }

    public function description(): string
    {
        return (string) $this->get('description', '');
    }

    public function excerpt(int $length = 100): string
    {
        $text = trim(strip_tags($this->description()));

        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . '...';
    }

    public function status(): int
    {
        return (int) $this->get('status', TaskEntity::STATUS_TODO);
    }

    public function statusLabel(): string
    {
        return TaskEntity::statusOptions()[$this->status()] ?? 'Unknown';
    }

    public function startDate(): string
    {
        return $this->dateOnly('start_date', 'startDate');
    }

    public function endDate(): string
    {
        return $this->dateOnly('end_date', 'endDate');
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

    public function statusVariant(): string
    {
        return match ((int) $this->status()) {
            0 => 'secondary',
            1 => 'warning',
            2 => 'success',
            default => 'dark',
        };
    }

    public function statusBadge(): string
    {
        return Badge::render(
            $this->statusLabel(),
            $this->statusVariant(),
        );
    }

    public function toDetailArray(): array
    {
        return [
            'id' => $this->id(),
            'title' => $this->title(),
            'statusLabel' => $this->statusBadge(),
            'startDate' => $this->startDate(),
            'endDate' => $this->endDate(),
            'description' => $this->description(),
            'createdAt' => $this->createdAt(),
            'updatedAt' => $this->updatedAt(),
            'createdBy' => $this->createdBy(),
            'updatedBy' => $this->updatedBy(),
        ];
    }

    private function dateOnly(string $snakeKey, string $camelKey): string
    {
        $value = $this->get($snakeKey, $this->get($camelKey));

        if ($value === null || $value === '') {
            return '-';
        }

        return date('d/m/Y', strtotime((string) $value));
    }
}

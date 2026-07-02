<?php

declare(strict_types=1);

namespace App\Data\Mes\Task;

use App\Data\Core\BaseEntity;
use App\Helpers\Translate;

final class TaskEntity extends BaseEntity
{
    public const STATUS_TODO = 0;
    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_DONE = 2;

    public function __construct(
        public ?int $id = null,
        public string $title = '',
        public ?string $description = null,
        public int $status = self::STATUS_TODO,
        public ?string $startDate = null,
        public ?string $endDate = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
        ?int $createdBy = null,
        ?int $updatedBy = null,
    ) {
        parent::__construct($createdAt, $updatedAt, $createdBy, $updatedBy);
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_TODO => Translate::t('To do'),
            self::STATUS_IN_PROGRESS => Translate::t('In progress'),
            self::STATUS_DONE => Translate::t('Done'),
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? Translate::t('Unknown');
    }

    public function rename(string $title): void
    {
        $this->title = trim($title);
    }

    public function rewriteDescription(?string $description): void
    {
        $this->description = $description !== null ? trim($description) : null;
    }

    public function changeStatus(int $status): void
    {
        $this->status = $status;
    }

    public function schedule(?string $startDate, ?string $endDate): void
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}

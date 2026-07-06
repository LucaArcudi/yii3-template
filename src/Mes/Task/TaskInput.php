<?php

declare(strict_types=1);

namespace App\Mes\Task;

use App\Data\Core\InputValue;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\Rule\Date\Date;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\ValidatorInterface;

use function array_key_exists;
use function is_scalar;
use function trim;

final class TaskInput implements RulesProviderInterface
{
    public ?int $id = null;
    public ?string $title = null;
    public ?string $description = null;
    public ?int $status = null;
    public ?string $startDate = null;
    public ?string $endDate = null;

    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    public function fill(array $data): self
    {
        $this->id = array_key_exists('id', $data)
            ? InputValue::intOrBoundary($data['id'], min: 1, max: InputValue::DB_INT_UNSIGNED_MAX)
            : $this->id;
        $this->title = array_key_exists('title', $data) ? trim((string) $data['title']) : $this->title;
        $this->description = array_key_exists('description', $data)
            ? trim((string) $data['description'])
            : $this->description;
        $this->status = array_key_exists('status', $data)
            ? InputValue::intOrBoundary($data['status'], min: 0, max: self::maxStatus())
            : $this->status;
        $this->startDate = $this->dateValue($data, 'start_date', 'startDate', $this->startDate);
        $this->endDate = $this->dateValue($data, 'end_date', 'endDate', $this->endDate);

        return $this;
    }

    public function validateCreate(): Result
    {
        return $this->validator->validate($this, $this->getRules());
    }

    public function validateUpdate(): Result
    {
        return $this->validator->validate($this, $this->getUpdateRules());
    }

    public function validateDelete(): Result
    {
        return $this->validator->validate($this, $this->getDeleteRules());
    }

    public function getRules(): iterable
    {
        return [
            'title' => [
                new Required(),
                new Length(min: 3, max: 255),
            ],
            'description' => [
                new Length(max: 5000),
            ],
            'status' => [
                new Required(),
                new Integer(min: 0, max: self::maxStatus()),
                new In([
                    TaskEntity::STATUS_TODO,
                    TaskEntity::STATUS_IN_PROGRESS,
                    TaskEntity::STATUS_DONE,
                ]),
            ],
            'startDate' => [
                new Date(format: 'php:Y-m-d', skipOnEmpty: true),
            ],
            'endDate' => [
                new Date(format: 'php:Y-m-d', skipOnEmpty: true),
            ],
        ];
    }

    public function getUpdateRules(): iterable
    {
        return [
            'id' => [
                new Required(),
                new Integer(min: 1, max: InputValue::DB_INT_UNSIGNED_MAX),
            ],
            ...$this->getRules(),
        ];
    }

    public function getDeleteRules(): iterable
    {
        return [
            'id' => [
                new Required(),
                new Integer(min: 1, max: InputValue::DB_INT_UNSIGNED_MAX),
            ],
        ];
    }

    public function toTask(?TaskEntity $existingTask = null, ?int $actorId = null): TaskEntity
    {
        return new TaskEntity(
            id: $this->id,
            title: $this->title ?? '',
            description: $this->description,
            status: $this->status ?? TaskEntity::STATUS_TODO,
            startDate: $this->startDate,
            endDate: $this->endDate,
            createdAt: $existingTask?->createdAt,
            updatedAt: $existingTask?->updatedAt,
            createdBy: $existingTask?->createdBy ?? $actorId,
            updatedBy: $actorId,
        );
    }

    private static function maxStatus(): int
    {
        return max(array_keys(TaskEntity::statusOptions()));
    }

    private function dateValue(array $data, string $snakeName, string $camelName, ?string $current): ?string
    {
        if (array_key_exists($snakeName, $data)) {
            return $this->nullableString($data[$snakeName]);
        }

        if (array_key_exists($camelName, $data)) {
            return $this->nullableString($data[$camelName]);
        }

        return $current;
    }

    private function nullableString(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

<?php

declare(strict_types=1);

namespace App\Mes\Task;

use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Date\Date;
use Yiisoft\Validator\Rule\Each;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\ValidatorInterface;

use function array_key_exists;
use function array_values;
use function is_array;
use function is_scalar;
use function trim;

final class TaskFilter implements RulesProviderInterface
{
    public ?string $id = null;
    public ?string $title = null;
    public ?string $description = null;
    /** @var string[] */
    public array $status = [];
    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?string $createdAt = null;

    private array $queryState = [];

    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    public function fill(array $data): self
    {
        $this->id = $this->stringValue($data['id'] ?? null, 20);
        $this->title = $this->stringValue($data['title'] ?? null, 255);
        $this->description = $this->stringValue($data['description'] ?? null, 5000);
        $this->status = $this->stringList($data['status'] ?? null, 20);
        $this->startDate = $this->stringValue($data['start_date'] ?? null, 30);
        $this->endDate = $this->stringValue($data['end_date'] ?? null, 30);
        $this->createdAt = $this->stringValue($data['created_at'] ?? null, 30);
        $this->queryState = $this->queryState($data);

        return $this;
    }

    public function validate(array $data): array
    {
        $this->fill($data);
        $result = $this->validator->validate($this, $this->getRules());

        return $this->toArray($result);
    }

    public function getRules(): iterable
    {
        return [
            'id' => [
                new Integer(min: 1, skipOnEmpty: true),
            ],
            'title' => [
                new Length(max: 50, skipOnEmpty: true),
            ],
            'description' => [
                new Length(max: 5000, skipOnEmpty: true),
            ],
            'status' => [
                new Each($this->statusItemRules(), skipOnEmpty: true),
            ],
            'startDate' => [
                new Date(format: 'php:Y-m-d', skipOnEmpty: true),
            ],
            'endDate' => [
                new Date(format: 'php:Y-m-d', skipOnEmpty: true),
            ],
            'createdAt' => [
                new Date(format: 'php:Y-m-d', skipOnEmpty: true),
            ],
        ];
    }

    public function getFilterRules(): array
    {
        $rules = $this->getRules();

        return [
            'id' => $rules['id'] ?? [],
            'title' => $rules['title'] ?? [],
            'description' => $rules['description'] ?? [],
            'status' => $rules['status'] ?? [],
            'start_date' => $rules['startDate'] ?? [],
            'end_date' => $rules['endDate'] ?? [],
            'created_at' => $rules['createdAt'] ?? [],
        ];
    }

    public function toArray(?Result $result = null): array
    {
        $result ??= $this->validator->validate($this, $this->getRules());
        $filters = [];

        if ($this->id !== null && $result->isPropertyValid('id')) {
            $filters['id'] = (int) $this->id;
        }

        if ($this->title !== null && $result->isPropertyValid('title')) {
            $filters['title'] = $this->title;
        }

        if ($this->description !== null && $result->isPropertyValid('description')) {
            $filters['description'] = $this->description;
        }

        $status = $this->validIntList($this->status, $this->statusItemRules());
        if ($status !== []) {
            $filters['status'] = $status;
        }

        if ($this->startDate !== null && $result->isPropertyValid('startDate')) {
            $filters['start_date'] = $this->startDate;
        }

        if ($this->endDate !== null && $result->isPropertyValid('endDate')) {
            $filters['end_date'] = $this->endDate;
        }

        if ($this->createdAt !== null && $result->isPropertyValid('createdAt')) {
            $filters['created_at'] = $this->createdAt;
        }

        return [...$filters, ...$this->queryState];
    }

    private function statusItemRules(): array
    {
        return [
            new Integer(min: 0, skipOnEmpty: true),
            new In(array_keys(TaskEntity::statusOptions()), skipOnEmpty: true),
        ];
    }

    private function stringValue(mixed $value, int $_maxLength): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function stringList(mixed $value, int $maxLength): array
    {
        $values = is_array($value) ? $value : [$value];
        $normalized = [];

        foreach ($values as $item) {
            $item = $this->stringValue($item, $maxLength);

            if ($item !== null) {
                $normalized[] = $item;
            }
        }

        return $normalized;
    }

    private function validIntList(array $values, array $rules): array
    {
        $valid = [];

        foreach ($values as $value) {
            if (!$this->validator->validate($value, $rules)->isValid()) {
                continue;
            }

            $value = (int) $value;
            $valid[$value] = $value;
        }

        return array_values($valid);
    }

    private function queryState(array $data): array
    {
        $state = [];

        foreach (['sort', 'page', 'previous-page', 'display'] as $name) {
            if (!array_key_exists($name, $data)) {
                continue;
            }

            $value = $this->stringValue($data[$name], 200);

            if ($value !== null) {
                $state[$name] = $value;
            }
        }

        return $state;
    }
}

<?php

declare(strict_types=1);

namespace App\Data\Core\Permission;

use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Date\Date;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\ValidatorInterface;

use function array_key_exists;
use function is_scalar;
use function trim;

final class PermissionGroupFilter implements RulesProviderInterface
{
    public ?string $id = null;
    public ?string $name = null;
    public ?string $code = null;
    public ?string $createdAt = null;

    private array $queryState = [];

    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    public function fill(array $data): self
    {
        $this->id = $this->stringValue($data['id'] ?? null, 20);
        $this->name = $this->stringValue($data['name'] ?? null, 100);
        $this->code = $this->stringValue($data['code'] ?? null, 100);
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
            'name' => [
                new Length(max: 100, skipOnEmpty: true),
            ],
            'code' => [
                new Length(max: 100, skipOnEmpty: true),
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
            'name' => $rules['name'] ?? [],
            'code' => $rules['code'] ?? [],
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

        if ($this->name !== null && $result->isPropertyValid('name')) {
            $filters['name'] = $this->name;
        }

        if ($this->code !== null && $result->isPropertyValid('code')) {
            $filters['code'] = $this->code;
        }

        if ($this->createdAt !== null && $result->isPropertyValid('createdAt')) {
            $filters['created_at'] = $this->createdAt;
        }

        return [...$filters, ...$this->queryState];
    }

    private function stringValue(mixed $value, int $_maxLength): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function queryState(array $data): array
    {
        $state = [];

        foreach (['sort', 'page', 'previous-page'] as $name) {
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

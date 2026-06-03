<?php

declare(strict_types=1);

namespace App\Data\Core\User;

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

final class UserFilter implements RulesProviderInterface
{
    public ?string $id = null;
    public ?string $email = null;
    public ?string $name = null;
    public ?string $status = null;
    /** @var string[] */
    public array $roleIds = [];
    public ?string $lastLoginAt = null;

    /** @var int[]|null */
    private ?array $allowedRoleIds = null;
    private array $queryState = [];

    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function fill(array $data, ?array $allowedRoleIds = null): self
    {
        $this->id = $this->stringValue($data['id'] ?? null, 20);
        $this->email = $this->stringValue($data['email'] ?? null, 190);
        $this->name = $this->stringValue($data['name'] ?? null, 120);
        $this->status = $this->stringValue($data['status'] ?? null, 5);
        $this->roleIds = $this->stringList($data['role_ids'] ?? null, 20);
        $this->lastLoginAt = $this->stringValue($data['last_login_at'] ?? null, 30);
        $this->allowedRoleIds = $allowedRoleIds;
        $this->queryState = $this->queryState($data);

        return $this;
    }

    public function validate(array $data, ?array $allowedRoleIds = null): array
    {
        $this->fill($data, $allowedRoleIds);
        $result = $this->validator->validate($this, $this->getRules());

        return $this->toArray($result);
    }

    public function getRules(): iterable
    {
        return [
            'id' => [
                new Integer(min: 1, skipOnEmpty: true),
            ],
            'email' => [
                new Length(max: 190, skipOnEmpty: true),
            ],
            'name' => [
                new Length(max: 120, skipOnEmpty: true),
            ],
            'status' => [
                ...$this->statusRules(),
            ],
            'roleIds' => [
                new Each($this->roleIdRules(), skipOnEmpty: true),
            ],
            'lastLoginAt' => [
                new Date(format: 'php:Y-m-d', skipOnEmpty: true),
            ],
        ];
    }

    public function getFilterRules(): array
    {
        $rules = $this->getRules();

        return [
            'id' => $rules['id'] ?? [],
            'email' => $rules['email'] ?? [],
            'name' => $rules['name'] ?? [],
            'status' => $rules['status'] ?? [],
            'role_ids' => $rules['roleIds'] ?? [],
            'last_login_at' => $rules['lastLoginAt'] ?? [],
        ];
    }

    public function toArray(?Result $result = null): array
    {
        $result ??= $this->validator->validate($this, $this->getRules());
        $filters = [];

        if ($this->id !== null && $result->isPropertyValid('id')) {
            $filters['id'] = (int) $this->id;
        }

        if ($this->email !== null && $result->isPropertyValid('email')) {
            $filters['email'] = $this->email;
        }

        if ($this->name !== null && $result->isPropertyValid('name')) {
            $filters['name'] = $this->name;
        }

        if ($this->status !== null && $this->validator->validate($this->status, $this->statusRules())->isValid()) {
            $filters['status'] = (int) $this->status;
        }

        $roleIds = $this->validIntList($this->roleIds, $this->roleIdRules());
        if ($roleIds !== []) {
            $filters['role_ids'] = $roleIds;
        }

        if ($this->lastLoginAt !== null && $result->isPropertyValid('lastLoginAt')) {
            $filters['last_login_at'] = $this->lastLoginAt;
        }

        return [...$filters, ...$this->queryState];
    }

    private function statusRules(): array
    {
        return [
            new Integer(min: 0, skipOnEmpty: true),
            new In(array_keys(UserEntity::statusOptions()), skipOnEmpty: true),
        ];
    }

    private function roleIdRules(): array
    {
        $rules = [
            new Integer(min: 1, skipOnEmpty: true),
        ];

        if ($this->allowedRoleIds !== null) {
            $rules[] = new In($this->allowedRoleIds, skipOnEmpty: true);
        }

        return $rules;
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

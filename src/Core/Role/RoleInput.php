<?php

declare(strict_types=1);

namespace App\Core\Role;

use App\Data\Core\InputValue;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\ValidatorInterface;

use function is_array;
use function is_scalar;

final class RoleInput implements RulesProviderInterface
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $code = null;

    /**
     * @var int[]
     */
    public array $permissionIds = [];

    private bool $invalidPermissionSelection = false;

    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    public function fill(array $data): self
    {
        $this->id = array_key_exists('id', $data)
            ? InputValue::intOrBoundary($data['id'], min: 1, max: InputValue::DB_INT_UNSIGNED_MAX)
            : $this->id;
        $this->name = array_key_exists('name', $data) ? trim((string) $data['name']) : $this->name;
        $this->code = array_key_exists('code', $data) ? trim((string) $data['code']) : $this->code;
        $this->permissionIds = [];
        $this->invalidPermissionSelection = false;

        $values = $data['permission_ids'] ?? [];
        $values = is_array($values) ? $values : [$values];

        foreach ($values as $value) {
            if (!is_scalar($value)) {
                $this->invalidPermissionSelection = true;
                continue;
            }

            $stringValue = trim((string) $value);

            if ($stringValue === '') {
                continue;
            }

            $permissionId = InputValue::intOrBoundary(
                $stringValue,
                min: 1,
                max: InputValue::DB_INT_UNSIGNED_MAX,
            );

            if (!InputValue::inRange($permissionId, min: 1, max: InputValue::DB_INT_UNSIGNED_MAX)) {
                $this->invalidPermissionSelection = true;
                continue;
            }

            $this->permissionIds[$permissionId] = $permissionId;
        }

        $this->permissionIds = array_values($this->permissionIds);

        return $this;
    }

    public function setPermissionIds(array $permissionIds): self
    {
        $ids = [];

        foreach ($permissionIds as $permissionId) {
            $permissionId = InputValue::intOrBoundary(
                $permissionId,
                min: 1,
                max: InputValue::DB_INT_UNSIGNED_MAX,
            );

            if (InputValue::inRange($permissionId, min: 1, max: InputValue::DB_INT_UNSIGNED_MAX)) {
                $ids[$permissionId] = $permissionId;
            }
        }

        $this->permissionIds = array_values($ids);

        return $this;
    }

    public function hasInvalidPermissionSelection(): bool
    {
        return $this->invalidPermissionSelection;
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
            'name' => [
                new Required(),
                new Length(min: 3, max: 100),
            ],
            'code' => [
                new Required(),
                new Length(min: 3, max: 100),
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

    public function toRole(?RoleEntity $existingRole = null, ?int $actorId = null): RoleEntity
    {
        return new RoleEntity(
            id: $this->id,
            name: $this->name ?? '',
            code: $this->code ?? '',
            createdAt: $existingRole?->createdAt,
            updatedAt: $existingRole?->updatedAt,
            createdBy: $existingRole?->createdBy ?? $actorId,
            updatedBy: $actorId,
        );
    }
}

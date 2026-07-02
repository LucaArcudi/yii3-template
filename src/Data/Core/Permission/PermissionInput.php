<?php

declare(strict_types=1);

namespace App\Data\Core\Permission;

use App\Data\Core\InputValue;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\ValidatorInterface;

use function strtoupper;

final class PermissionInput implements RulesProviderInterface
{
    public ?int $id = null;
    public ?int $groupId = null;
    public ?string $groupName = null;
    public ?string $name = null;
    public ?string $code = null;
    public ?int $weight = 1;

    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    public function fill(array $data): self
    {
        $this->id = array_key_exists('id', $data)
            ? InputValue::intOrBoundary($data['id'], min: 1, max: InputValue::DB_INT_UNSIGNED_MAX)
            : $this->id;
        $this->groupId = array_key_exists('group_id', $data)
            ? InputValue::intOrBoundary($data['group_id'], min: 1, max: InputValue::DB_INT_UNSIGNED_MAX)
            : $this->groupId;
        $this->groupId = array_key_exists('groupId', $data)
            ? InputValue::intOrBoundary($data['groupId'], min: 1, max: InputValue::DB_INT_UNSIGNED_MAX)
            : $this->groupId;
        $this->groupName = array_key_exists('group_name', $data)
            ? trim((string) $data['group_name'])
            : $this->groupName;
        $this->groupName = array_key_exists('groupName', $data)
            ? trim((string) $data['groupName'])
            : $this->groupName;
        $this->name = array_key_exists('name', $data) ? trim((string) $data['name']) : $this->name;
        $this->code = array_key_exists('code', $data) ? strtoupper(trim((string) $data['code'])) : $this->code;
        $this->weight = array_key_exists('weight', $data)
            ? InputValue::intOrBoundary($data['weight'], min: 1, max: 1000)
            : $this->weight;

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
            'name' => [
                new Required(),
                new Length(min: 3, max: 100),
            ],
            'groupId' => [
                new Required(),
                new Integer(min: 1, max: InputValue::DB_INT_UNSIGNED_MAX),
            ],
            'code' => [
                new Required(),
                new Length(min: 3, max: 100),
            ],
            'weight' => [
                new Required(),
                new Integer(min: 1, max: 1000),
            ],
        ];
    }

    public function normalizeForGroup(PermissionGroupEntity $group): self
    {
        $this->groupId = $group->id;
        $this->groupName = $group->name;

        return $this;
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

    public function toPermission(?PermissionEntity $existingPermission = null, ?int $actorId = null): PermissionEntity
    {
        return new PermissionEntity(
            id: $this->id,
            groupId: $this->groupId,
            name: $this->name ?? '',
            code: $this->code ?? '',
            weight: $this->weight ?? 1,
            groupName: $this->groupName,
            createdAt: $existingPermission?->createdAt,
            updatedAt: $existingPermission?->updatedAt,
            createdBy: $existingPermission?->createdBy ?? $actorId,
            updatedBy: $actorId,
        );
    }
}

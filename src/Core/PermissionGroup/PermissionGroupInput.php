<?php

declare(strict_types=1);

namespace App\Core\PermissionGroup;

use App\Shared\Data\InputValue;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\ValidatorInterface;

use function preg_replace;
use function strtoupper;
use function trim;

final class PermissionGroupInput implements RulesProviderInterface
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $code = null;

    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    public function fill(array $data): self
    {
        $this->id = array_key_exists('id', $data)
            ? InputValue::intOrBoundary($data['id'], min: 1, max: InputValue::DB_INT_UNSIGNED_MAX)
            : $this->id;
        $this->name = array_key_exists('name', $data) ? trim((string) $data['name']) : $this->name;
        $this->code = array_key_exists('code', $data) ? $this->normalizeCode((string) $data['code']) : $this->code;

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

    public function getRules(): iterable
    {
        return [
            'name' => [
                new Required(),
                new Length(min: 2, max: 100),
            ],
            'code' => [
                new Required(),
                new Length(min: 2, max: 100),
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

    public function toGroup(?PermissionGroupEntity $existingGroup = null, ?int $actorId = null): PermissionGroupEntity
    {
        return new PermissionGroupEntity(
            id: $this->id,
            name: $this->name ?? '',
            code: $this->code ?? '',
            createdAt: $existingGroup?->createdAt,
            updatedAt: $existingGroup?->updatedAt,
            createdBy: $existingGroup?->createdBy ?? $actorId,
            updatedBy: $actorId,
        );
    }

    private function normalizeCode(string $code): string
    {
        $code = strtoupper(trim($code));
        $code = (string) preg_replace('/[^A-Z0-9]+/', '_', $code);

        return trim($code, '_');
    }
}

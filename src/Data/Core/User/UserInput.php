<?php

declare(strict_types=1);

namespace App\Data\Core\User;

use App\Data\Core\InputValue;
use App\Helpers\Translate;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\ValidatorInterface;

use function array_values;
use function is_array;
use function is_scalar;

final class UserInput implements RulesProviderInterface
{
    public ?int $id = null;
    public ?string $email = null;
    public ?string $name = null;
    public ?string $password = null;
    public ?string $passwordRepeat = null;
    public ?string $currentPassword = null;
    public ?int $status = null;
    /** @var int[] */
    public array $roleIds = [];

    private bool $invalidRoleSelection = false;

    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    public function fill(array $data): self
    {
        $this->id = array_key_exists('id', $data)
            ? InputValue::intOrBoundary($data['id'], min: 1, max: InputValue::DB_INT_UNSIGNED_MAX)
            : $this->id;
        $this->email = array_key_exists('email', $data) ? mb_strtolower(trim((string) $data['email'])) : $this->email;
        $this->name = array_key_exists('name', $data) ? trim((string) $data['name']) : $this->name;
        $this->password = array_key_exists('password', $data) ? (string) $data['password'] : $this->password;
        $this->passwordRepeat = array_key_exists('password_repeat', $data)
            ? (string) $data['password_repeat']
            : $this->passwordRepeat;
        $this->currentPassword = array_key_exists('current_password', $data)
            ? (string) $data['current_password']
            : $this->currentPassword;
        $this->status = array_key_exists('status', $data)
            ? InputValue::intOrBoundary($data['status'], min: 0, max: self::maxStatus())
            : $this->status;

        if (array_key_exists('role_ids', $data)) {
            $this->fillRoleIds($data['role_ids']);
        }

        return $this;
    }

    /**
     * @param int[] $roleIds
     */
    public function setRoleIds(array $roleIds): self
    {
        $ids = [];

        foreach ($roleIds as $roleId) {
            $roleId = InputValue::intOrBoundary($roleId, min: 1, max: InputValue::DB_INT_UNSIGNED_MAX);

            if (InputValue::inRange($roleId, min: 1, max: InputValue::DB_INT_UNSIGNED_MAX)) {
                $ids[$roleId] = $roleId;
            }
        }

        $this->roleIds = array_values($ids);

        return $this;
    }

    public function hasInvalidRoleSelection(): bool
    {
        return $this->invalidRoleSelection;
    }

    public function validateCreate(): Result
    {
        return $this->validatePasswordRepeat(
            $this->validator->validate($this, $this->getCreateRules()),
            true,
        );
    }

    public function validateUpdate(): Result
    {
        $result = $this->validator->validate($this, $this->getUpdateRules());

        if ($this->password !== null && $this->password !== '') {
            $length = mb_strlen($this->password);

            if ($length < 8 || $length > 255) {
                $result = $result->addError(
                    Translate::t('La password deve contenere tra 8 e 255 caratteri.'),
                    valuePath: ['password'],
                );
            }
        }

        return $this->validatePasswordRepeat($result, false);
    }

    public function validateProfile(): Result
    {
        return $this->validator->validate($this, $this->getProfileRules());
    }

    public function validateEmailChange(): Result
    {
        return $this->validator->validate($this, $this->getEmailChangeRules());
    }

    public function validateDelete(): Result
    {
        return $this->validator->validate($this, $this->getDeleteRules());
    }

    public function getRules(): iterable
    {
        return [
            'email' => [
                new Required(),
                new Email(),
                new Length(max: 190),
            ],
            'name' => [
                new Required(),
                new Length(min: 2, max: 120),
            ],
            'status' => [
                new Required(),
                new Integer(min: 0, max: self::maxStatus()),
                new In([
                    UserEntity::STATUS_ACTIVE,
                    UserEntity::STATUS_INACTIVE,
                ]),
            ],
        ];
    }

    public function getCreateRules(): iterable
    {
        return [
            ...$this->getRules(),
            'password' => [
                new Required(),
                new Length(min: 8, max: 255),
            ],
            'passwordRepeat' => [
                new Required(),
                new Length(min: 8, max: 255),
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

    public function getProfileRules(): iterable
    {
        return [
            'id' => [
                new Required(),
                new Integer(min: 1, max: InputValue::DB_INT_UNSIGNED_MAX),
            ],
            'name' => [
                new Required(),
                new Length(min: 2, max: 120),
            ],
        ];
    }

    public function getEmailChangeRules(): iterable
    {
        return [
            'id' => [
                new Required(),
                new Integer(min: 1, max: InputValue::DB_INT_UNSIGNED_MAX),
            ],
            'email' => [
                new Required(),
                new Email(),
                new Length(max: 190),
            ],
            'currentPassword' => [
                new Required(),
                new Length(max: 255),
            ],
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

    public function toUser(
        string $passwordHash,
        ?UserEntity $existingUser = null,
        ?int $actorId = null,
    ): UserEntity {
        return new UserEntity(
            id: $this->id,
            email: $this->email ?? '',
            passwordHash: $passwordHash,
            name: $this->name ?? '',
            status: $this->status ?? UserEntity::STATUS_ACTIVE,
            createdAt: $existingUser?->createdAt,
            updatedAt: $existingUser?->updatedAt,
            lastLoginAt: $existingUser?->lastLoginAt,
            rememberTokenHash: $existingUser?->rememberTokenHash,
            passwordChangedAt: $existingUser?->passwordChangedAt,
            passwordExpiresAt: $existingUser?->passwordExpiresAt,
            passwordResetSelector: $existingUser?->passwordResetSelector,
            passwordResetTokenHash: $existingUser?->passwordResetTokenHash,
            passwordResetTokenExpiresAt: $existingUser?->passwordResetTokenExpiresAt,
            createdBy: $existingUser?->createdBy ?? $actorId,
            updatedBy: $actorId,
        );
    }

    private function fillRoleIds(mixed $value): void
    {
        $this->invalidRoleSelection = false;

        if ($value === null || $value === '') {
            $this->roleIds = [];

            return;
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $roleIds = [];

        foreach ($value as $item) {
            if (!is_scalar($item)) {
                $this->invalidRoleSelection = true;
                continue;
            }

            $stringValue = trim((string) $item);

            if ($stringValue === '') {
                continue;
            }

            $roleId = InputValue::intOrBoundary($stringValue, min: 1, max: InputValue::DB_INT_UNSIGNED_MAX);

            if (!InputValue::inRange($roleId, min: 1, max: InputValue::DB_INT_UNSIGNED_MAX)) {
                $this->invalidRoleSelection = true;
                continue;
            }

            $roleIds[$roleId] = $roleId;
        }

        $this->roleIds = array_values($roleIds);
    }

    private static function maxStatus(): int
    {
        return max(array_keys(UserEntity::statusOptions()));
    }

    private function validatePasswordRepeat(Result $result, bool $passwordRequired): Result
    {
        $password = $this->password ?? '';
        $passwordRepeat = $this->passwordRepeat ?? '';
        $hasPassword = $password !== '';
        $hasPasswordRepeat = $passwordRepeat !== '';

        if (!$passwordRequired && !$hasPassword && !$hasPasswordRepeat) {
            return $result;
        }

        if (!$passwordRequired && !$hasPassword) {
            return $result->addError(
                Translate::t('Compila la password prima di confermarla.'),
                valuePath: ['password'],
            );
        }

        if (!$hasPassword || !$hasPasswordRepeat) {
            return $passwordRequired
                ? $result
                : $result->addError(Translate::t('Ripeti la nuova password.'), valuePath: ['passwordRepeat']);
        }

        if ($password !== $passwordRepeat) {
            $result = $result->addError(Translate::t('Le password non coincidono.'), valuePath: ['passwordRepeat']);
        }

        return $result;
    }
}

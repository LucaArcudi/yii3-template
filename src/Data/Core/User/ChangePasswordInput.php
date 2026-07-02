<?php

declare(strict_types=1);

namespace App\Data\Core\User;

use App\Helpers\Translate;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\ValidatorInterface;

final class ChangePasswordInput implements RulesProviderInterface
{
    public ?string $currentPassword = null;
    public ?string $password = null;
    public ?string $passwordRepeat = null;
    public ?string $token = null;

    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    public function fill(array $data): self
    {
        $this->currentPassword = array_key_exists('current_password', $data)
            ? (string) $data['current_password']
            : $this->currentPassword;
        $this->password = array_key_exists('password', $data) ? (string) $data['password'] : $this->password;
        $this->passwordRepeat = array_key_exists('password_repeat', $data)
            ? (string) $data['password_repeat']
            : $this->passwordRepeat;
        $this->token = array_key_exists('token', $data) ? trim((string) $data['token']) : $this->token;

        return $this;
    }

    public function validateChangePassword(bool $requiresCurrentPassword): Result
    {
        $result = $this->validator->validate($this, $this->getRules($requiresCurrentPassword));

        if ($this->password !== $this->passwordRepeat) {
            $result = $result->addError(Translate::t('Le password non coincidono.'), valuePath: ['passwordRepeat']);
        }

        return $result;
    }

    public function getRules(bool $requiresCurrentPassword = true): iterable
    {
        $rules = [
            'password' => [
                new Required(),
                new Length(min: 8, max: 255),
            ],
            'passwordRepeat' => [
                new Required(),
                new Length(min: 8, max: 255),
            ],
        ];

        if ($requiresCurrentPassword) {
            $rules = [
                'currentPassword' => [
                    new Required(),
                    new Length(max: 255),
                ],
                ...$rules,
            ];
        }

        return $rules;
    }
}

<?php

declare(strict_types=1);

namespace App\Data\Core\User;

use Yiisoft\Validator\Result;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\ValidatorInterface;

final class LoginInput implements RulesProviderInterface
{
    public ?string $email = null;
    public ?string $password = null;
    public bool $rememberMe = true;

    public function __construct(
        private ValidatorInterface $validator
    ){
    }

    public function validateLogin(): Result
    {
        return $this->validator->validate($this, $this->getRules());
    }

    public function fill(array $data): self
    {
        $this->email = isset($data['email']) ? mb_strtolower(trim((string) $data['email'])) : $this->email;
        $this->password = isset($data['password']) ? (string) $data['password'] : $this->password;
        $this->rememberMe = isset($data['remember_me']) && (string) $data['remember_me'] === '1';

        return $this;
    }

    public function getRules(): iterable
    {
        return [
            'email' => [
                new Required(),
                new Email(),
                new Length(max: 190),
            ],
            'password' => [
                new Required(),
                new Length(min: 8, max: 255),
            ],
        ];
    }
}

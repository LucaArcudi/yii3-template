<?php

declare(strict_types=1);

namespace App\Data\Core\User;

use Yiisoft\Validator\Result;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\ValidatorInterface;

final class RegisterInput implements RulesProviderInterface
{
    public ?string $email = null;
    public ?string $name = null;
    public ?string $password = null;
    public ?string $passwordRepeat = null;
    public ?string $captcha = null;
    public ?string $website = null;

    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {}

    public function fill(array $data): self
    {
        $this->email = isset($data['email']) ? mb_strtolower(trim((string) $data['email'])) : $this->email;
        $this->name = isset($data['name']) ? trim((string) $data['name']) : $this->name;
        $this->password = isset($data['password']) ? (string) $data['password'] : $this->password;
        $this->passwordRepeat = isset($data['password_repeat']) ? (string) $data['password_repeat'] : $this->passwordRepeat;
        $this->captcha = isset($data['captcha']) ? trim((string) $data['captcha']) : $this->captcha;
        $this->website = isset($data['website']) ? trim((string) $data['website']) : $this->website;

        return $this;
    }

    public function validateRegister(): Result
    {
        $result = $this->validator->validate($this, $this->getRules());

        if ($this->password !== $this->passwordRepeat) {
            $result = $result->addError('Le password non coincidono.', valuePath: ['passwordRepeat']);
        }

        return $result;
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
            'password' => [
                new Required(),
                new Length(min: 8, max: 255),
            ],
            'passwordRepeat' => [
                new Required(),
                new Length(min: 8, max: 255),
            ],
            'captcha' => [
                new Required(),
                new Length(max: 16, skipOnEmpty: true),
            ],
        ];
    }
}

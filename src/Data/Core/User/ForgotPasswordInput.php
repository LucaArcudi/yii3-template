<?php

declare(strict_types=1);

namespace App\Data\Core\User;

use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\RulesProviderInterface;
use Yiisoft\Validator\ValidatorInterface;

final class ForgotPasswordInput implements RulesProviderInterface
{
    public ?string $email = null;

    public function __construct(
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function fill(array $data): self
    {
        $this->email = isset($data['email']) ? mb_strtolower(trim((string) $data['email'])) : $this->email;

        return $this;
    }

    public function validateForgotPassword(): Result
    {
        return $this->validator->validate($this, $this->getRules());
    }

    public function getRules(): iterable
    {
        return [
            'email' => [
                new Required(),
                new Email(),
                new Length(max: 190),
            ],
        ];
    }
}

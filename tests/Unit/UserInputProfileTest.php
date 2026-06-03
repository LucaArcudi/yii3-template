<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Data\Core\User\UserInput;
use Codeception\Test\Unit;
use Yiisoft\Validator\Validator;

final class UserInputProfileTest extends Unit
{
    public function testProfileUpdateValidatesOnlyEditableProfileFields(): void
    {
        $result = $this->newInput([
            'id' => 7,
            'name' => 'Mario Rossi',
            'email' => '',
        ])->validateProfile();

        self::assertTrue($result->isValid());
    }

    public function testEmailChangeRequiresCurrentPassword(): void
    {
        $result = $this->newInput([
            'id' => 7,
            'email' => 'nuova@example.test',
            'current_password' => '',
        ])->validateEmailChange();

        self::assertFalse($result->isValid());
        self::assertFalse($result->isPropertyValid('currentPassword'));
    }

    public function testEmailChangeValidatesEmailFormat(): void
    {
        $result = $this->newInput([
            'id' => 7,
            'email' => 'email-non-valida',
            'current_password' => 'password-corrente',
        ])->validateEmailChange();

        self::assertFalse($result->isValid());
        self::assertFalse($result->isPropertyValid('email'));
    }

    private function newInput(array $data): UserInput
    {
        return (new UserInput(new Validator()))->fill($data);
    }
}

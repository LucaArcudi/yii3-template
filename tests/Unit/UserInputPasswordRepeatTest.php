<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Data\Core\User\UserEntity;
use App\Data\Core\User\UserInput;
use Codeception\Test\Unit;
use Yiisoft\Validator\Validator;

final class UserInputPasswordRepeatTest extends Unit
{
    public function testCreateRequiresMatchingPasswordRepeat(): void
    {
        $result = $this->newInput([
            'email' => 'user@example.test',
            'name' => 'Mario Rossi',
            'password' => 'password-valida',
            'password_repeat' => 'password-diversa',
            'status' => UserEntity::STATUS_ACTIVE,
        ])->validateCreate();

        self::assertFalse($result->isValid());
        self::assertSame(
            ['Le password non coincidono.'],
            $result->getPropertyErrorMessages('passwordRepeat'),
        );
    }

    public function testUpdateRequiresPasswordRepeatWhenPasswordIsChanged(): void
    {
        $result = $this->newInput([
            'id' => 7,
            'email' => 'user@example.test',
            'name' => 'Mario Rossi',
            'password' => 'password-valida',
            'password_repeat' => '',
            'status' => UserEntity::STATUS_ACTIVE,
        ])->validateUpdate();

        self::assertFalse($result->isValid());
        self::assertSame(
            ['Ripeti la nuova password.'],
            $result->getPropertyErrorMessages('passwordRepeat'),
        );
    }

    public function testUpdateRejectsPasswordRepeatWithoutPassword(): void
    {
        $result = $this->newInput([
            'id' => 7,
            'email' => 'user@example.test',
            'name' => 'Mario Rossi',
            'password' => '',
            'password_repeat' => 'password-valida',
            'status' => UserEntity::STATUS_ACTIVE,
        ])->validateUpdate();

        self::assertFalse($result->isValid());
        self::assertSame(
            ['Compila la password prima di confermarla.'],
            $result->getPropertyErrorMessages('password'),
        );
    }

    public function testUpdateAllowsEmptyPasswordFieldsWhenPasswordIsUnchanged(): void
    {
        $result = $this->newInput([
            'id' => 7,
            'email' => 'user@example.test',
            'name' => 'Mario Rossi',
            'password' => '',
            'password_repeat' => '',
            'status' => UserEntity::STATUS_ACTIVE,
        ])->validateUpdate();

        self::assertTrue($result->isValid());
    }

    private function newInput(array $data): UserInput
    {
        return (new UserInput(new Validator()))->fill($data);
    }
}

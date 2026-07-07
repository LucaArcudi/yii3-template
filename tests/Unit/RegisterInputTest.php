<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Core\User\RegisterInput;
use Codeception\Test\Unit;
use Yiisoft\Validator\Validator;

final class RegisterInputTest extends Unit
{
    public function testRegistrationRequiresCaptchaAnswer(): void
    {
        $result = $this->newInput([
            'email' => 'user@example.test',
            'name' => 'Mario Rossi',
            'password' => 'password-valida',
            'password_repeat' => 'password-valida',
        ])->validateRegister();

        self::assertFalse($result->isValid());
        self::assertNotEmpty($result->getPropertyErrorMessages('captcha'));
    }

    public function testRegistrationAcceptsMatchingPasswordAndCaptchaAnswer(): void
    {
        $result = $this->newInput([
            'email' => 'user@example.test',
            'name' => 'Mario Rossi',
            'password' => 'password-valida',
            'password_repeat' => 'password-valida',
            'captcha' => '7',
        ])->validateRegister();

        self::assertTrue($result->isValid());
    }

    private function newInput(array $data): RegisterInput
    {
        return (new RegisterInput(new Validator()))->fill($data);
    }
}

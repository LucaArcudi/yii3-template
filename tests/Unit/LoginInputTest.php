<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Data\Core\User\LoginInput;
use Codeception\Test\Unit;
use Yiisoft\Validator\Validator;

final class LoginInputTest extends Unit
{
    public function testRememberMeIsEnabledByDefault(): void
    {
        $input = new LoginInput(new Validator());

        self::assertTrue($input->rememberMe);
    }

    public function testRememberMeCanBeDisabledBySubmittedForm(): void
    {
        $input = (new LoginInput(new Validator()))->fill([
            'email' => 'user@example.test',
            'password' => 'password-valida',
        ]);

        self::assertFalse($input->rememberMe);
    }

    public function testPasswordUsesConfiguredLengthPolicy(): void
    {
        $result = (new LoginInput(new Validator()))
            ->fill([
                'email' => 'user@example.test',
                'password' => 'short',
            ])
            ->validateLogin();

        self::assertFalse($result->isValid());
        self::assertFalse($result->isPropertyValid('password'));
    }
}

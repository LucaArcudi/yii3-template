<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Services\Core\PasswordHasher;
use Codeception\Test\Unit;

final class PasswordHasherTest extends Unit
{
    public function testHashUsesArgon2idAndVerifiesPassword(): void
    {
        $hasher = new PasswordHasher();

        $hash = $hasher->hash('111QQQqqq!');

        self::assertStringStartsWith('$argon2id$', $hash);
        self::assertTrue($hasher->verify('111QQQqqq!', $hash));
        self::assertFalse($hasher->verify('wrong-password', $hash));
    }
}

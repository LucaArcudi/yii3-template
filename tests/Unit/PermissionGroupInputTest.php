<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Core\PermissionGroup\PermissionGroupInput;
use Codeception\Test\Unit;
use Yiisoft\Validator\Validator;

final class PermissionGroupInputTest extends Unit
{
    public function testCodeIsNormalizedToUppercase(): void
    {
        $input = $this->newInput([
            'name' => 'User',
            'code' => 'user access',
        ]);

        self::assertSame('USER_ACCESS', $input->code);
    }

    public function testNameAndCodeAreRequired(): void
    {
        $result = $this->newInput([
            'name' => '',
            'code' => '',
        ])->validateCreate();

        self::assertFalse($result->isValid());
        self::assertFalse($result->isPropertyValid('name'));
        self::assertFalse($result->isPropertyValid('code'));
    }

    private function newInput(array $data): PermissionGroupInput
    {
        return (new PermissionGroupInput(new Validator()))->fill($data);
    }
}

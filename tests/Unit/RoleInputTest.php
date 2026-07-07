<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Core\Role\RoleInput;
use Codeception\Test\Unit;
use Yiisoft\Validator\Validator;

final class RoleInputTest extends Unit
{
    public function testNameAndCodeRespectDatabaseVarcharLength(): void
    {
        $result = $this->newInput([
            'name' => str_repeat('A', 101),
            'code' => str_repeat('B', 101),
        ])->validateCreate();

        self::assertFalse($result->isValid());
        self::assertFalse($result->isPropertyValid('name'));
        self::assertFalse($result->isPropertyValid('code'));
    }

    private function newInput(array $data): RoleInput
    {
        return (new RoleInput(new Validator()))->fill($data);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Data\Core\Permission\PermissionInput;
use App\Data\Core\Permission\PermissionGroupEntity;
use Codeception\Test\Unit;
use Yiisoft\Validator\Validator;

final class PermissionInputTest extends Unit
{
    public function testCreateRequiresPermissionGroup(): void
    {
        $result = $this->newInput([
            'name' => 'Creazione utenti',
            'group_id' => '',
            'code' => 'ACCESS',
        ])->validateCreate();

        self::assertFalse($result->isValid());
        self::assertFalse($result->isPropertyValid('groupId'));
    }

    public function testCreateAcceptsPermissionGroup(): void
    {
        $result = $this->newInput([
            'name' => 'Creazione utenti',
            'group_id' => '3',
            'code' => 'ACCESS',
        ])->validateCreate();

        self::assertTrue($result->isValid());
    }

    public function testNormalizeForGroupKeepsSubmittedPermissionNaming(): void
    {
        $input = $this->newInput([
            'name' => 'task.manage',
            'group_id' => '3',
            'code' => 'task_manage',
        ])->normalizeForGroup(new PermissionGroupEntity(
            id: 3,
            name: 'task',
            code: 'TASK',
        ));

        self::assertSame('task.manage', $input->name);
        self::assertSame('TASK_MANAGE', $input->code);
        self::assertSame('task', $input->groupName);
    }

    public function testNameAndCodeRespectDatabaseVarcharLength(): void
    {
        $result = $this->newInput([
            'name' => str_repeat('A', 101),
            'group_id' => '3',
            'code' => str_repeat('B', 101),
        ])->validateCreate();

        self::assertFalse($result->isValid());
        self::assertFalse($result->isPropertyValid('name'));
        self::assertFalse($result->isPropertyValid('code'));
    }

    private function newInput(array $data): PermissionInput
    {
        return (new PermissionInput(new Validator()))->fill($data);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Data\Core\Permission\PermissionFilter;
use App\Mes\Task\TaskFilter;
use App\Data\Core\User\UserFilter;
use Codeception\Test\Unit;
use Yiisoft\Validator\Validator;

final class FilterTest extends Unit
{
    public function testTaskFilterTrimsAndDropsInvalidValues(): void
    {
        self::assertSame(
            [
                'title' => 'Deploy',
                'status' => [0, 2],
                'start_date' => '2026-05-01',
                'created_at' => '2026-04-30',
                'sort' => '-id',
            ],
            (new TaskFilter(new Validator()))->validate([
                'id' => 'nope',
                'title' => '  Deploy  ',
                'status' => ['0', 'bad', '2', '7'],
                'start_date' => '2026-05-01',
                'end_date' => 'bad',
                'created_at' => '2026-04-30',
                'sort' => '-id',
            ]),
        );
    }

    public function testUserFilterKeepsOnlyAllowedRoles(): void
    {
        self::assertSame(
            [
                'email' => 'USER@EXAMPLE.test',
                'status' => 1,
                'role_ids' => [2],
            ],
            (new UserFilter(new Validator()))->validate(
                [
                    'email' => '  USER@EXAMPLE.test  ',
                    'status' => '1',
                    'role_ids' => ['2', '4', 'x'],
                ],
                [1, 2, 3],
            ),
        );
    }

    public function testPermissionFilterDropsInvalidNumberAndDate(): void
    {
        self::assertSame(
            [
                'name' => 'Access',
                'group_name' => 'Security',
            ],
            (new PermissionFilter(new Validator()))->validate([
                'name' => ' Access ',
                'group_name' => ' Security ',
                'weight' => '0',
                'created_at' => '2026-02-31',
            ]),
        );
    }

    public function testPermissionFilterDropsOverLengthValuesInsteadOfTruncatingThem(): void
    {
        self::assertSame(
            [],
            (new PermissionFilter(new Validator()))->validate([
                'name' => str_repeat('A', 101),
            ]),
        );
    }

}

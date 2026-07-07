<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Core\Permission\PermissionInput;
use App\Core\Role\RoleInput;
use App\Mes\Task\TaskInput;
use App\Core\User\UserEntity;
use App\Core\User\UserInput;
use App\Widgets\Inputs\DateInput;
use App\Widgets\Inputs\TextInput;
use Codeception\Test\Unit;
use Yiisoft\Validator\Rule\Date\Date;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Validator;

final class InputIntegerValidationTest extends Unit
{
    public function testPermissionInputRejectsHugeWeightBeforePersistence(): void
    {
        $input = (new PermissionInput(new Validator()))->fill([
            'name' => 'Accesso admin',
            'group_id' => '3',
            'code' => 'ADMIN_ACCESS',
            'weight' => $this->hugeInteger(),
        ]);

        $result = $input->validateCreate();

        self::assertFalse($result->isValid());
        self::assertFalse($result->isPropertyValid('weight'));
    }

    public function testTaskAndUserStatusRejectHugeIntegers(): void
    {
        $taskResult = (new TaskInput(new Validator()))->fill([
            'title' => 'Deploy release',
            'description' => '',
            'status' => $this->hugeInteger(),
        ])->validateCreate();

        $userResult = (new UserInput(new Validator()))->fill([
            'email' => 'user@example.test',
            'name' => 'Mario Rossi',
            'password' => 'password-valida',
            'password_repeat' => 'password-valida',
            'status' => $this->hugeInteger(),
        ])->validateCreate();

        self::assertFalse($taskResult->isPropertyValid('status'));
        self::assertFalse($userResult->isPropertyValid('status'));
    }

    public function testTaskInputRejectsInvalidDatesBeforePersistence(): void
    {
        $result = (new TaskInput(new Validator()))->fill([
            'title' => 'Deploy release',
            'description' => '',
            'status' => '1',
            'start_date' => '2026-13-01',
            'end_date' => 'nope',
        ])->validateCreate();

        self::assertFalse($result->isValid());
        self::assertFalse($result->isPropertyValid('startDate'));
        self::assertFalse($result->isPropertyValid('endDate'));
    }

    public function testRoleAndUserSelectionsRejectHugeIdsWithoutUnsafeCast(): void
    {
        $roleInput = (new RoleInput(new Validator()))->fill([
            'name' => 'Admin',
            'code' => 'ADMIN',
            'permission_ids' => [$this->hugeInteger()],
        ]);
        $userInput = (new UserInput(new Validator()))->fill([
            'email' => 'user@example.test',
            'name' => 'Mario Rossi',
            'password' => 'password-valida',
            'password_repeat' => 'password-valida',
            'status' => UserEntity::STATUS_ACTIVE,
            'role_ids' => [$this->hugeInteger()],
        ]);

        self::assertTrue($roleInput->hasInvalidPermissionSelection());
        self::assertTrue($userInput->hasInvalidRoleSelection());
        self::assertSame([], $roleInput->permissionIds);
        self::assertSame([], $userInput->roleIds);
    }

    public function testIntegerFormRulesRenderNativeNumberBounds(): void
    {
        $html = TextInput::render(
            name: 'weight',
            label: 'Peso',
            value: '1',
            validationRules: [new Integer(min: 1, max: 1000)],
        );

        self::assertStringContainsString('type="number"', $html);
        self::assertStringContainsString('min="1"', $html);
        self::assertStringContainsString('max="1000"', $html);
        self::assertStringContainsString('step="1"', $html);
    }

    public function testLengthFormRulesRenderCustomValidationWithoutNativeTruncation(): void
    {
        $html = TextInput::render(
            name: 'name',
            label: 'Nome',
            value: 'Accesso admin',
            validationRules: [new Length(min: 3, max: 100)],
        );

        self::assertStringContainsString('data-input-min-length="3"', $html);
        self::assertStringContainsString('data-input-max-length="100"', $html);
        self::assertStringNotContainsString('minlength="3"', $html);
        self::assertStringNotContainsString('maxlength="100"', $html);
        self::assertStringContainsString('data-validation-feedback="true"', $html);
    }

    public function testDateFormRulesRenderSharedDateInputMarkup(): void
    {
        $html = DateInput::render(
            name: 'start_date',
            label: 'Data inizio',
            value: '2026-05-01',
            validationRules: [new Date(format: 'php:Y-m-d', skipOnEmpty: true)],
        );

        self::assertStringContainsString('type="text"', $html);
        self::assertStringContainsString('app-form-input app-form-input--date has-validation', $html);
        self::assertStringContainsString('app-date-input', $html);
        self::assertStringContainsString('data-date-picker="true"', $html);
        self::assertStringContainsString('data-validation-feedback="true"', $html);
    }

    private function hugeInteger(): string
    {
        return str_repeat('9', 80);
    }
}

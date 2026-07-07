<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Widgets\Crud\CrudActions;
use Codeception\Test\Unit;
use Yiisoft\Yii\View\Renderer\Csrf;

final class CrudActionsWidgetTest extends Unit
{
    public function testGroupRendersStandardCrudButtons(): void
    {
        $html = CrudActions::group(
            [
                CrudActions::viewLink('/user/view/7', label: 'Apri'),
                CrudActions::updateLink('/user/update/7', label: 'Modifica'),
                CrudActions::deleteTrigger('user-delete-modal-7', label: 'Elimina'),
            ],
            'Azioni utente #7',
        );

        self::assertStringContainsString('aria-label="Azioni utente #7"', $html);
        self::assertStringContainsString('href="/user/view/7"', $html);
        self::assertStringContainsString('data-bs-target="#user-delete-modal-7"', $html);
        self::assertStringContainsString('app-task-action-btn', $html);
    }

    public function testGroupRendersMutedFallbackWhenNoButtonsAreAvailable(): void
    {
        self::assertSame(
            '<span class="text-muted">-</span>',
            CrudActions::group([], 'Azioni non disponibili'),
        );
    }

    public function testDeleteModalRendersSharedConfirmationFooter(): void
    {
        $html = CrudActions::deleteModal(
            id: 'user-delete-modal-7',
            title: 'Elimina utente',
            action: '/user/delete/7',
            body: CrudActions::deleteBody(
                'Stai eliminando l\'utente <strong>Admin</strong>.',
                ['ID record' => '#7'],
            ),
            csrf: new Csrf('token-value', '_csrf', 'X-CSRF-Token'),
        );

        self::assertStringContainsString('id="user-delete-modal-7"', $html);
        self::assertStringContainsString('action="/user/delete/7"', $html);
        self::assertStringContainsString('name="_csrf"', $html);
        self::assertStringContainsString('Elimina definitivamente', $html);
        self::assertStringContainsString('app-task-view__meta-grid', $html);
    }

    public function testPageActionsRenderVisibleLabels(): void
    {
        $update = CrudActions::updatePageLink('/user/update/7', label: 'Modifica');
        $delete = CrudActions::deletePageTrigger('user-delete-modal-7', label: 'Elimina utente');

        self::assertStringContainsString('href="/user/update/7"', $update);
        self::assertStringContainsString('>Modifica</a>', $update);
        self::assertStringContainsString('btn-warning', $update);
        self::assertStringContainsString('data-bs-target="#user-delete-modal-7"', $delete);
        self::assertStringContainsString('Elimina utente', $delete);
    }
}

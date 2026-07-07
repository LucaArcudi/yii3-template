<?php

declare(strict_types=1);

use App\Core\Role\RolePresenter;
use App\Helpers\Translate;
use App\Widgets\BackButton;
use App\Widgets\Card;
use App\Widgets\Crud\CrudActions;
use App\Widgets\DataView\Detail;
use App\Widgets\EntityLogList;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\DetailView\DataField;
use Yiisoft\Yii\View\Renderer\Csrf;

/** @var RolePresenter $role */
/** @var array $permissionGroups */
/** @var array $logs */
/** @var bool $canViewLogs */
/** @var bool $canUpdate */
/** @var bool $canDelete */
/** @var string $backUrl */
/** @var string $currentUrl */
/** @var Csrf $csrf */

$this->setTitle($role->name());
$this->setParameter('pageIcon', 'pe-7s-id');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Ruoli'), 'url' => '/role'],
    ['label' => $role->name()],
]);

$data = $role->toDetailArray();
$roleId = (int) $role->id();
$deleteModalId = 'role-delete-modal-' . $roleId;
$permissionsCount = array_sum(array_map(
    static fn(array $group): int => count($group['items'] ?? []),
    $permissionGroups,
));
$pageActions = BackButton::render($backUrl);

if ($canUpdate) {
    $pageActions .= CrudActions::updatePageLink(
        '/role/update/' . $roleId . '?_return=' . rawurlencode($currentUrl),
    );
}

if ($canDelete) {
    $deleteModalBody = CrudActions::deleteBody(
        Translate::t('Stai eliminando il ruolo {name}. Dopo la conferma il record non sara piu recuperabile.', ['name' => '<strong>' . Html::encode($role->name()) . '</strong>']),
        [
            Translate::t('ID record') => '#' . $roleId,
            Translate::t('Codice') => '<code>' . Html::encode($role->code()) . '</code>',
        ],
    );

    $pageActions .= CrudActions::deletePageTrigger($deleteModalId, label: Translate::t('Elimina ruolo'));

    $this->setParameter(
        'pageModals',
        CrudActions::deleteModal(
            id: $deleteModalId,
            title: Translate::t('Elimina ruolo'),
            action: '/role/delete/' . $roleId,
            body: $deleteModalBody,
            csrf: $csrf,
        ),
    );
}

$this->setParameter(
    'pageActions',
    (string) Html::div(
        $pageActions,
        ['class' => 'app-page-actions'],
    )->encode(false),
);

$permissionsBody = '';

if ($permissionGroups === []) {
    $permissionsBody .= (string) Html::div(
        Translate::t('Questo ruolo non ha ancora permessi associati.'),
        ['class' => 'alert alert-light mb-0'],
    );
} else {
    $permissionsBody .= '<div class="app-permission-summary">';

    foreach ($permissionGroups as $group) {
        $items = $group['items'] ?? [];

        $permissionsBody .= '<section class="app-permission-summary__group">';
        $permissionsBody .= '<div class="app-permission-summary__header">';
        $permissionsBody .= '<div class="app-permission-summary__title">' . Html::encode((string) ($group['label'] ?? Translate::t('Generale'))) . '</div>';
        $permissionsBody .= (string) Html::span(
            (string) count($items),
            ['class' => ['badge', 'rounded-pill', 'text-bg-light']],
        );
        $permissionsBody .= '</div>';
        $permissionsBody .= '<div class="app-permission-summary__items">';

        foreach ($items as $item) {
            $permissionsBody .= '<div class="app-permission-chip">';
            $permissionsBody .= '<div class="app-permission-chip__name">' . Html::encode((string) ($item['name'] ?? '')) . '</div>';
            $permissionsBody .= '<code class="app-permission-chip__code">' . Html::encode((string) ($item['code'] ?? '')) . '</code>';
            $permissionsBody .= '</div>';
        }

        $permissionsBody .= '</div>';
        $permissionsBody .= '</section>';
    }

    $permissionsBody .= '</div>';
}

echo Detail::render(
    title: $role->name(),
    data: $data,
    variant: 'warning',
    fields: [
        new DataField('id', label: 'ID'),
        new DataField('name', label: Translate::t('Nome')),
        new DataField('code', label: Translate::t('Codice')),
        new DataField('createdBy', label: Translate::t('Creato da')),
        new DataField('updatedBy', label: Translate::t('Aggiornato da')),
        new DataField('createdAt', label: Translate::t('Creato il')),
        new DataField('updatedAt', label: Translate::t('Aggiornato il')),
    ],
);

if ($canViewLogs) {
    echo EntityLogList::render($logs);
}

echo Card::render(
    title: Translate::t('Permessi associati'),
    body: $permissionsBody,
    variant: 'info',
    tools: (string) Html::span(
        Translate::t('{count} totali', ['count' => $permissionsCount]),
        ['class' => ['badge', 'rounded-pill', 'text-bg-light']],
    ),
    subtitle: Translate::t('Raggruppati tramite permission_group.'),
    icon: 'fa-solid fa-shield-halved',
);

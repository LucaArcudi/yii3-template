<?php

declare(strict_types=1);

use App\Core\Permission\PermissionPresenter;
use App\Shared\Helpers\Translate;
use App\Shared\Widgets\BackButton;
use App\Shared\Widgets\Crud\CrudActions;
use App\Shared\Widgets\DataView\Detail;
use App\Shared\Widgets\EntityLogList;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\DetailView\DataField;
use Yiisoft\Yii\View\Renderer\Csrf;

/** @var PermissionPresenter $permission */
/** @var array $logs */
/** @var bool $canViewLogs */
/** @var bool $canUpdate */
/** @var bool $canDelete */
/** @var string $backUrl */
/** @var string $currentUrl */
/** @var Csrf $csrf */

$this->setTitle($permission->name());
$this->setParameter('pageIcon', 'pe-7s-key');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Permessi'), 'url' => '/permission'],
    ['label' => $permission->name()],
]);

$data = $permission->toDetailArray();
$permissionId = (int) $permission->id();
$deleteModalId = 'permission-delete-modal-' . $permissionId;
$pageActions = BackButton::render($backUrl);

if ($canUpdate) {
    $pageActions .= CrudActions::updatePageLink(
        '/permission/update/' . $permissionId . '?_return=' . rawurlencode($currentUrl),
    );
}

if ($canDelete) {
    $deleteModalBody = CrudActions::deleteBody(
        Translate::t('Stai eliminando il permesso {name}. Dopo la conferma il record non sara piu recuperabile.', ['name' => '<strong>' . Html::encode($permission->name()) . '</strong>']),
        [
            Translate::t('ID record') => '#' . $permissionId,
            Translate::t('Codice') => '<code>' . Html::encode($permission->code()) . '</code>',
        ],
    );

    $pageActions .= CrudActions::deletePageTrigger($deleteModalId, label: Translate::t('Elimina permesso'));

    $this->setParameter(
        'pageModals',
        CrudActions::deleteModal(
            id: $deleteModalId,
            title: Translate::t('Elimina permesso'),
            action: '/permission/delete/' . $permissionId,
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

echo Detail::render(
    title: $permission->name(),
    data: $data,
    variant: 'info',
    fields: [
        new DataField('id', label: 'ID'),
        new DataField('groupName', label: Translate::t('Gruppo')),
        new DataField('name', label: Translate::t('Nome')),
        new DataField('code', label: Translate::t('Codice')),
        new DataField('weight', label: Translate::t('Peso')),
        new DataField('createdBy', label: Translate::t('Creato da')),
        new DataField('updatedBy', label: Translate::t('Aggiornato da')),
        new DataField('createdAt', label: Translate::t('Creato il')),
        new DataField('updatedAt', label: Translate::t('Aggiornato il')),
    ],
);

if ($canViewLogs) {
    echo EntityLogList::render($logs);
}

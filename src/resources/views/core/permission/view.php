<?php

declare(strict_types=1);

use App\Data\Core\Permission\PermissionPresenter;
use App\Widgets\BackButton;
use App\Widgets\Crud\CrudActions;
use App\Widgets\DataView\Detail;
use App\Widgets\EntityLogList;
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
    ['label' => 'Dashboard', 'url' => '/'],
    ['label' => 'Permessi', 'url' => '/permission'],
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
        'Stai eliminando il permesso <strong>' . Html::encode($permission->name()) . '</strong>. Dopo la conferma il record non sara piu recuperabile.',
        [
            'ID record' => '#' . $permissionId,
            'Codice' => '<code>' . Html::encode($permission->code()) . '</code>',
        ],
    );

    $pageActions .= CrudActions::deletePageTrigger($deleteModalId, label: 'Elimina permesso');

    $this->setParameter(
        'pageModals',
        CrudActions::deleteModal(
            id: $deleteModalId,
            title: 'Elimina permesso',
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
        new DataField('groupName', label: 'Gruppo'),
        new DataField('name', label: 'Nome'),
        new DataField('code', label: 'Codice'),
        new DataField('weight', label: 'Peso'),
        new DataField('createdBy', label: 'Creato da'),
        new DataField('updatedBy', label: 'Aggiornato da'),
        new DataField('createdAt', label: 'Creato il'),
        new DataField('updatedAt', label: 'Aggiornato il'),
    ],
);

if ($canViewLogs) {
    echo EntityLogList::render($logs);
}

<?php

declare(strict_types=1);

use App\Data\Core\Permission\PermissionGroupPresenter;
use App\Widgets\BackButton;
use App\Widgets\Crud\CrudActions;
use App\Widgets\DataView\Detail;
use App\Widgets\EntityLogList;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\DetailView\DataField;
use Yiisoft\Yii\View\Renderer\Csrf;

/** @var PermissionGroupPresenter $group */
/** @var array $logs */
/** @var bool $canViewLogs */
/** @var bool $canUpdate */
/** @var bool $canDelete */
/** @var string $backUrl */
/** @var string $currentUrl */
/** @var Csrf $csrf */

$this->setTitle($group->name());
$this->setParameter('pageIcon', 'fa-solid fa-layer-group');
$this->setParameter('breadcrumbs', [
    ['label' => 'Dashboard', 'url' => '/'],
    ['label' => 'Gruppi permessi', 'url' => '/permission-group'],
    ['label' => $group->name()],
]);

$data = $group->toDetailArray();
$groupId = (int) $group->id();
$deleteModalId = 'permission-group-delete-modal-' . $groupId;
$pageActions = BackButton::render($backUrl);

if ($canUpdate) {
    $pageActions .= CrudActions::updatePageLink(
        '/permission-group/update/' . $groupId . '?_return=' . rawurlencode($currentUrl),
    );
}

if ($canDelete) {
    $deleteModalBody = CrudActions::deleteBody(
        'Stai eliminando il gruppo <strong>' . Html::encode($group->name()) . '</strong>. Dopo la conferma il record non sara piu recuperabile.',
        [
            'ID record' => '#' . $groupId,
            'Codice' => '<code>' . Html::encode($group->code()) . '</code>',
        ],
    );

    $pageActions .= CrudActions::deletePageTrigger($deleteModalId, label: 'Elimina gruppo');

    $this->setParameter(
        'pageModals',
        CrudActions::deleteModal(
            id: $deleteModalId,
            title: 'Elimina gruppo permessi',
            action: '/permission-group/delete/' . $groupId,
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
    title: $group->name(),
    data: $data,
    variant: 'info',
    fields: [
        new DataField('id', label: 'ID'),
        new DataField('name', label: 'Nome'),
        new DataField('code', label: 'Codice'),
        new DataField('createdBy', label: 'Creato da'),
        new DataField('updatedBy', label: 'Aggiornato da'),
        new DataField('createdAt', label: 'Creato il'),
        new DataField('updatedAt', label: 'Aggiornato il'),
    ],
);

if ($canViewLogs) {
    echo EntityLogList::render($logs);
}

<?php

declare(strict_types=1);

use App\Mes\Task\TaskPresenter;
use App\Helpers\Translate;
use App\Widgets\BackButton;
use App\Widgets\Crud\CrudActions;
use App\Widgets\DataView\Detail;
use App\Widgets\EntityLogList;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\DetailView\DataField;
use Yiisoft\Yii\View\Renderer\Csrf;

/** @var TaskPresenter $task */
/** @var array $logs */
/** @var bool $canViewLogs */
/** @var bool $canUpdate */
/** @var bool $canDelete */
/** @var string $backUrl */
/** @var string $currentUrl */
/** @var Csrf $csrf */

$this->setTitle($task->title());
$this->setParameter('pageIcon', 'pe-7s-news-paper');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Tasks'), 'url' => '/task'],
    ['label' => $task->title()],
]);

$data = $task->toDetailArray();
$taskId = (int) $task->id();
$deleteModalId = 'task-delete-modal-' . $taskId;
$pageActions = BackButton::render($backUrl);

if ($canUpdate) {
    $pageActions .= CrudActions::updatePageLink(
        '/task/update/' . $taskId . '?_return=' . rawurlencode($currentUrl),
    );
}

if ($canDelete) {
    $deleteModalBody = CrudActions::deleteBody(
        Translate::t('Stai eliminando la task {title}. Dopo la conferma il record non sara piu recuperabile.', ['title' => '<strong>' . Html::encode($task->title()) . '</strong>']),
        [
            Translate::t('ID record') => '#' . $taskId,
            Translate::t('Stato corrente') => $task->statusBadge(),
        ],
    );

    $pageActions .= CrudActions::deletePageTrigger($deleteModalId, label: Translate::t('Elimina task'));

    $this->setParameter(
        'pageModals',
        CrudActions::deleteModal(
            id: $deleteModalId,
            title: Translate::t('Elimina task'),
            action: '/task/delete/' . $taskId,
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

$after = (string) Html::tag('hr')->void();
$after .= (string) Html::div(
    nl2br(Html::encode($task->description())),
    ['class' => 'mb-3 app-task-view__description'],
)->encode(false);

echo Detail::render(
    title: $task->title(),
    data: $data,
    variant: 'info',
    after: $after,
    fields: [
        new DataField('id', label: 'ID'),
        new DataField('title', label: Translate::t('Titolo')),
        Detail::htmlField('statusLabel', label: Translate::t('Stato')),
        new DataField('startDate', label: Translate::t('Data inizio')),
        new DataField('endDate', label: Translate::t('Data fine')),
        new DataField('createdBy', label: Translate::t('Creato da')),
        new DataField('updatedBy', label: Translate::t('Aggiornato da')),
        new DataField('createdAt', label: Translate::t('Creato il')),
        new DataField('updatedAt', label: Translate::t('Aggiornato il')),
    ],
);

if ($canViewLogs) {
    echo EntityLogList::render($logs);
}

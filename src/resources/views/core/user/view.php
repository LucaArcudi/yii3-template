<?php

declare(strict_types=1);

use App\Data\Core\User\UserPresenter;
use App\Widgets\BackButton;
use App\Widgets\Card;
use App\Widgets\Crud\CrudActions;
use App\Widgets\DataView\Detail;
use App\Widgets\EntityLogList;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\DetailView\DataField;
use Yiisoft\Yii\View\Renderer\Csrf;

/** @var UserPresenter $user */
/** @var array $roles */
/** @var array $logs */
/** @var bool $canViewLogs */
/** @var bool $canUpdate */
/** @var bool $canDelete */
/** @var string $backUrl */
/** @var string $currentUrl */
/** @var Csrf $csrf */

$this->setTitle($user->name());
$this->setParameter('pageIcon', 'pe-7s-users');
$this->setParameter('breadcrumbs', [
    ['label' => 'Dashboard', 'url' => '/'],
    ['label' => 'Utenti', 'url' => '/user'],
    ['label' => $user->name()],
]);

$data = $user->toDetailArray();
$userId = (int) $user->id();
$deleteModalId = 'user-delete-modal-' . $userId;
$pageActions = BackButton::render($backUrl);

if ($canUpdate) {
    $pageActions .= CrudActions::updatePageLink(
        '/user/update/' . $userId . '?_return=' . rawurlencode($currentUrl),
    );
}

if ($canDelete) {
    $deleteModalBody = CrudActions::deleteBody(
        'Stai eliminando l\'utente <strong>' . Html::encode($user->name()) . '</strong>. Dopo la conferma il record non sara piu recuperabile.',
        [
            'ID record' => '#' . $userId,
            'Email' => Html::encode($user->email()),
        ],
    );

    $pageActions .= CrudActions::deletePageTrigger($deleteModalId, label: 'Elimina utente');

    $this->setParameter(
        'pageModals',
        CrudActions::deleteModal(
            id: $deleteModalId,
            title: 'Elimina utente',
            action: '/user/delete/' . $userId,
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

$rolesBody = '';

if ($roles === []) {
    $rolesBody .= (string) Html::div(
        'Questo utente non ha ancora ruoli associati.',
        ['class' => 'alert alert-light mb-0'],
    );
} else {
    $rolesBody .= '<div class="app-permission-summary__items">';

    foreach ($roles as $role) {
        $rolesBody .= '<div class="app-permission-chip">';
        $rolesBody .= '<div class="app-permission-chip__name">' . Html::encode((string) ($role['name'] ?? '')) . '</div>';
        $rolesBody .= '<code class="app-permission-chip__code">' . Html::encode((string) ($role['code'] ?? '')) . '</code>';
        $rolesBody .= '</div>';
    }

    $rolesBody .= '</div>';
}

echo Detail::render(
    title: $user->name(),
    data: $data,
    variant: 'secondary',
    fields: [
        new DataField('id', label: 'ID'),
        new DataField('name', label: 'Nome'),
        new DataField('email', label: 'Email'),
        Detail::htmlField('statusLabel', label: 'Stato'),
        new DataField('createdBy', label: 'Creato da'),
        new DataField('updatedBy', label: 'Aggiornato da'),
        new DataField('createdAt', label: 'Creato il'),
        new DataField('updatedAt', label: 'Aggiornato il'),
        new DataField('lastLoginAt', label: 'Ultimo accesso'),
        new DataField('passwordChangedAt', label: 'Password cambiata il'),
        new DataField('passwordExpiresAt', label: 'Scadenza password'),
    ],
);

if ($canViewLogs) {
    echo EntityLogList::render($logs);
}

echo Card::render(
    title: 'Ruoli associati',
    body: $rolesBody,
    variant: 'info',
    tools: (string) Html::span(
        (string) count($roles) . ' totali',
        ['class' => ['badge', 'rounded-pill', 'text-bg-light']],
    ),
    subtitle: 'Associazione user_role gestita lato utente.',
    icon: 'fa-solid fa-user-tag',
);

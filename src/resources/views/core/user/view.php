<?php

declare(strict_types=1);

use App\Data\Core\User\UserPresenter;
use App\Helpers\Translate;
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
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Utenti'), 'url' => '/user'],
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
        Translate::t('Stai eliminando l\'utente {name}. Dopo la conferma il record non sara piu recuperabile.', ['name' => '<strong>' . Html::encode($user->name()) . '</strong>']),
        [
            Translate::t('ID record') => '#' . $userId,
            'Email' => Html::encode($user->email()),
        ],
    );

    $pageActions .= CrudActions::deletePageTrigger($deleteModalId, label: Translate::t('Elimina utente'));

    $this->setParameter(
        'pageModals',
        CrudActions::deleteModal(
            id: $deleteModalId,
            title: Translate::t('Elimina utente'),
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
        Translate::t('Questo utente non ha ancora ruoli associati.'),
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
        new DataField('name', label: Translate::t('Nome')),
        new DataField('email', label: 'Email'),
        Detail::htmlField('statusLabel', label: Translate::t('Stato')),
        new DataField('createdBy', label: Translate::t('Creato da')),
        new DataField('updatedBy', label: Translate::t('Aggiornato da')),
        new DataField('createdAt', label: Translate::t('Creato il')),
        new DataField('updatedAt', label: Translate::t('Aggiornato il')),
        new DataField('lastLoginAt', label: Translate::t('Ultimo accesso')),
        new DataField('passwordChangedAt', label: Translate::t('Password cambiata il')),
        new DataField('passwordExpiresAt', label: Translate::t('Scadenza password')),
    ],
);

if ($canViewLogs) {
    echo EntityLogList::render($logs);
}

echo Card::render(
    title: Translate::t('Ruoli associati'),
    body: $rolesBody,
    variant: 'info',
    tools: (string) Html::span(
        Translate::t('{count} totali', ['count' => count($roles)]),
        ['class' => ['badge', 'rounded-pill', 'text-bg-light']],
    ),
    subtitle: Translate::t('Associazione user_role gestita lato utente.'),
    icon: 'fa-solid fa-user-tag',
);

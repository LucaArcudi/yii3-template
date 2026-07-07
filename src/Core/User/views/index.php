<?php

declare(strict_types=1);

use App\Core\User\UserEntity;
use App\Core\User\UserPresenter;
use App\Helpers\Translate;
use App\Widgets\Crud\CrudActions;
use App\Widgets\DataView\Grid;
use App\Widgets\Filters\FilterModal;
use Yiisoft\Data\Db\QueryDataReader;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\View\Renderer\Csrf;

/** @var QueryDataReader $reader */
/** @var array $filters */
/** @var array $filterRules */
/** @var array $roleOptions */
/** @var Csrf $csrf */
/** @var callable $gridUrlCreator */
/** @var string $currentUrl */
/** @var bool $canCreate */
/** @var bool $canView */
/** @var bool $canUpdate */
/** @var bool $canDelete */

$this->setTitle(Translate::t('Utenti'));
$this->setParameter('pageIcon', 'pe-7s-users');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Utenti')],
]);

$filterModalId = 'user-filter-modal';
$filterFields = [
    [
        'name' => 'name',
        'label' => Translate::t('Nome'),
        'widget' => 'textFilter',
        'placeholder' => Translate::t('Cerca per nome'),
        'icon' => 'fa-solid fa-magnifying-glass',
        'columnClass' => 'col-12 col-lg-6',
        'validationRules' => $filterRules['name'] ?? [],
    ],
    [
        'name' => 'email',
        'label' => 'Email',
        'widget' => 'textFilter',
        'placeholder' => Translate::t('Cerca per email'),
        'icon' => 'fa-regular fa-envelope',
        'columnClass' => 'col-12 col-lg-6',
        'validationRules' => $filterRules['email'] ?? [],
    ],
    [
        'name' => 'status',
        'label' => Translate::t('Stato'),
        'widget' => 'selectFilter',
        'options' => UserEntity::statusOptions(),
        'prompt' => Translate::t('Tutti'),
        'icon' => 'fa-solid fa-signal',
        'columnClass' => 'col-12 col-lg-4',
        'validationRules' => $filterRules['status'] ?? [],
    ],
    [
        'name' => 'role_ids',
        'label' => Translate::t('Ruoli'),
        'widget' => 'multiSelectFilter',
        'options' => $roleOptions,
        'placeholder' => Translate::t('Tutti i ruoli'),
        'icon' => 'fa-solid fa-user-tag',
        'columnClass' => 'col-12 col-lg-8',
        'validationRules' => $filterRules['role_ids'] ?? [],
    ],
];

$createButton = $canCreate
    ? '<a href="/user/create?_return=' . rawurlencode($currentUrl) . '" class="btn btn-primary btn-shadow btn-sm">'
        . '<i class="fa-solid fa-plus me-1"></i>' . Html::encode(Translate::t('Nuovo utente'))
        . '</a>'
    : '';

$toolbar = <<<HTML
<div class="d-flex flex-wrap align-items-center gap-2 justify-content-end">
    %s
    %s
</div>
HTML;

$toolbar = sprintf(
    $toolbar,
    FilterModal::button($filterModalId, FilterModal::activeCount($filters, $filterFields)),
    $createButton,
);
$userModals = [
    $filterModalId => FilterModal::render(
        id: $filterModalId,
        title: Translate::t('Filtri utenti'),
        action: '/user',
        filters: $filters,
        fields: $filterFields,
        variant: 'secondary',
    ),
];

$grid = Grid::render(
    title: Translate::t('Elenco utenti'),
    reader: $reader,
    toolbar: $toolbar,
    variant: 'secondary',
    urlCreator: $gridUrlCreator,
    columns: [
        new DataColumn(
            property: 'name',
            header: Translate::t('Utente'),
            bodyAttributes: ['class' => 'text-wrap'],
            content: static function (array|object $row): string {
                $user = new UserPresenter($row);

                return sprintf(
                    '<div class="app-task-cell"><div class="app-task-cell__title">%s</div><div class="app-task-cell__meta">%s</div></div>',
                    htmlspecialchars($user->name(), ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($user->email(), ENT_QUOTES, 'UTF-8'),
                );
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'status',
            header: Translate::t('Stato'),
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                return (new UserPresenter($row))->statusBadge();
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'last_login_at',
            header: Translate::t('Ultimo accesso'),
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                return (new UserPresenter($row))->lastLoginAt();
            },
        ),
        new DataColumn(
            header: Translate::t('Azioni'),
            withSorting: false,
            headerAttributes: ['style' => 'width: 10rem;'],
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row) use ($csrf, $currentUrl, &$userModals, $canView, $canUpdate, $canDelete): string {
                $user = new UserPresenter($row);
                $id = (int) $user->id();
                $deleteModalId = 'user-delete-modal-' . $id;
                $buttons = [];

                if ($canView) {
                    $buttons[] = CrudActions::viewLink(
                        '/user/view/' . $id . '?_return=' . rawurlencode($currentUrl),
                    );
                }

                if ($canUpdate) {
                    $buttons[] = CrudActions::updateLink(
                        '/user/update/' . $id . '?_return=' . rawurlencode($currentUrl),
                    );
                }

                if ($canDelete) {
                    $buttons[] = CrudActions::deleteTrigger($deleteModalId);
                    $deleteModalBody = CrudActions::deleteBody(
                        Translate::t('Stai eliminando l\'utente {name}. Dopo la conferma il record non sara piu recuperabile.', ['name' => '<strong>' . Html::encode($user->name()) . '</strong>']),
                        [
                            Translate::t('ID record') => '#' . $id,
                            'Email' => Html::encode($user->email()),
                        ],
                    );

                    $userModals[$deleteModalId] = CrudActions::deleteModal(
                        id: $deleteModalId,
                        title: Translate::t('Elimina utente'),
                        action: '/user/delete/' . $id,
                        body: $deleteModalBody,
                        csrf: $csrf,
                    );
                }

                return CrudActions::group($buttons, Translate::t('Azioni utente #{id}', ['id' => $id]));
            },
            encodeContent: false,
        ),
    ],
);

if ($userModals !== []) {
    $this->setParameter('pageModals', implode('', $userModals));
}

echo $grid;

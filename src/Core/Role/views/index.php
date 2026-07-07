<?php

declare(strict_types=1);

use App\Core\Role\RolePresenter;
use App\Helpers\Translate;
use App\Widgets\Crud\CrudActions;
use App\Widgets\DataView\Grid;
use App\Widgets\Filters\FilterBar;
use Yiisoft\Data\Db\QueryDataReader;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\View\Renderer\Csrf;

/** @var QueryDataReader $reader */
/** @var array $filters */
/** @var array $filterRules */
/** @var Csrf $csrf */
/** @var callable $gridUrlCreator */
/** @var string $currentUrl */
/** @var bool $canCreate */
/** @var bool $canView */
/** @var bool $canUpdate */
/** @var bool $canDelete */

$this->setTitle(Translate::t('Ruoli'));
$this->setParameter('pageIcon', 'pe-7s-id');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Ruoli')],
]);

$filterFields = [
    [
        'name' => 'name',
        'label' => Translate::t('Nome'),
        'widget' => 'textFilter',
        'placeholder' => Translate::t('Cerca per nome'),
        'icon' => 'fa-solid fa-magnifying-glass',
        'columnClass' => 'col-12 col-lg-4',
        'validationRules' => $filterRules['name'] ?? [],
    ],
    [
        'name' => 'code',
        'label' => Translate::t('Codice'),
        'widget' => 'textFilter',
        'placeholder' => Translate::t('Es. ADMIN'),
        'icon' => 'fa-solid fa-id-badge',
        'columnClass' => 'col-12 col-lg-4',
        'validationRules' => $filterRules['code'] ?? [],
    ],
    [
        'name' => 'created_at',
        'label' => Translate::t('Creato il'),
        'widget' => 'dateFilter',
        'placeholder' => 'YYYY-MM-DD',
        'icon' => 'fa-regular fa-calendar',
        'columnClass' => 'col-12 col-lg-4',
        'validationRules' => $filterRules['created_at'] ?? [],
    ],
];

$createButton = $canCreate
    ? '<a href="/role/create?_return=' . rawurlencode($currentUrl) . '" class="btn btn-primary btn-shadow btn-sm">'
        . '<i class="fa-solid fa-plus me-1"></i>' . Html::encode(Translate::t('Nuovo ruolo'))
        . '</a>'
    : '';

$toolbar = <<<HTML
<div class="d-flex flex-wrap align-items-center gap-2 justify-content-end">
    %s
</div>
HTML;

$toolbar = sprintf(
    $toolbar,
    $createButton,
);
$filterBar = FilterBar::render(
    view: $this,
    action: '/role',
    filters: $filters,
    fields: $filterFields,
    title: Translate::t('Filtri ruoli'),
    icon: 'fa-solid fa-filter',
);
$roleModals = ['' => '']; // Inizializzato con un elemento vuoto per evitare problemi di implode in caso di array vuoto

$grid = Grid::render(
    title: Translate::t('Elenco ruoli'),
    reader: $reader,
    toolbar: $toolbar,
    variant: 'warning',
    urlCreator: $gridUrlCreator,
    columns: [
        new DataColumn(
            property: 'name',
            header: Translate::t('Nome'),
            bodyAttributes: ['class' => 'text-wrap'],
            content: static function (array|object $row): string {
                $role = new RolePresenter($row);

                return sprintf(
                    '<div class="app-task-cell"><div class="app-task-cell__title">%s</div><div class="app-task-cell__meta">%s</div></div>',
                    htmlspecialchars($role->name(), ENT_QUOTES, 'UTF-8'),
                    Html::encode(Translate::t('Ruolo #{id}', ['id' => (int) $role->id()])),
                );
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'code',
            header: Translate::t('Codice'),
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                $role = new RolePresenter($row);

                return '<code class="small">' . htmlspecialchars($role->code(), ENT_QUOTES, 'UTF-8') . '</code>';
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'created_at',
            header: Translate::t('Creato il'),
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                return (new RolePresenter($row))->createdAt();
            },
        ),
        new DataColumn(
            header: Translate::t('Azioni'),
            withSorting: false,
            headerAttributes: ['style' => 'width: 10rem;'],
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row) use ($csrf, $currentUrl, &$roleModals, $canView, $canUpdate, $canDelete): string {
                $role = new RolePresenter($row);
                $id = (int) $role->id();
                $deleteModalId = 'role-delete-modal-' . $id;
                $buttons = [];

                if ($canView) {
                    $buttons[] = CrudActions::viewLink(
                        '/role/view/' . $id . '?_return=' . rawurlencode($currentUrl),
                    );
                }

                if ($canUpdate) {
                    $buttons[] = CrudActions::updateLink(
                        '/role/update/' . $id . '?_return=' . rawurlencode($currentUrl),
                    );
                }

                if ($canDelete) {
                    $buttons[] = CrudActions::deleteTrigger($deleteModalId);
                    $deleteModalBody = CrudActions::deleteBody(
                        Translate::t('Stai eliminando il ruolo {name}. Dopo la conferma il record non sara piu recuperabile.', ['name' => '<strong>' . Html::encode($role->name()) . '</strong>']),
                        [
                            Translate::t('ID record') => '#' . $id,
                            Translate::t('Codice') => '<code>' . Html::encode($role->code()) . '</code>',
                        ],
                    );

                    $roleModals[$deleteModalId] = CrudActions::deleteModal(
                        id: $deleteModalId,
                        title: Translate::t('Elimina ruolo'),
                        action: '/role/delete/' . $id,
                        body: $deleteModalBody,
                        csrf: $csrf,
                    );
                }

                return CrudActions::group($buttons, Translate::t('Azioni ruolo #{id}', ['id' => $id]));
            },
            encodeContent: false,
        ),
    ],
);

if ($roleModals !== []) {
    $this->setParameter('pageModals', implode('', $roleModals));
}

echo $filterBar;
echo $grid;

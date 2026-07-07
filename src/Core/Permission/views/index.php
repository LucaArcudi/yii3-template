<?php

declare(strict_types=1);

use App\Core\Permission\PermissionPresenter;
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
/** @var Csrf $csrf */
/** @var callable $gridUrlCreator */
/** @var string $currentUrl */
/** @var bool $canCreate */
/** @var bool $canView */
/** @var bool $canUpdate */
/** @var bool $canDelete */

$this->setTitle(Translate::t('Permessi'));
$this->setParameter('pageIcon', 'pe-7s-key');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Permessi')],
]);

$filterModalId = 'permission-filter-modal';
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
        'name' => 'code',
        'label' => Translate::t('Codice'),
        'widget' => 'textFilter',
        'placeholder' => Translate::t('Es. ACCESS'),
        'icon' => 'fa-solid fa-key',
        'columnClass' => 'col-12 col-lg-6',
        'validationRules' => $filterRules['code'] ?? [],
    ],
    [
        'name' => 'group_name',
        'label' => Translate::t('Gruppo'),
        'widget' => 'textFilter',
        'placeholder' => Translate::t('Cerca per gruppo'),
        'icon' => 'fa-solid fa-layer-group',
        'columnClass' => 'col-12 col-lg-6',
        'validationRules' => $filterRules['group_name'] ?? [],
    ],
    [
        'name' => 'weight',
        'label' => Translate::t('Peso'),
        'widget' => 'textFilter',
        'placeholder' => '1',
        'icon' => 'fa-solid fa-weight-hanging',
        'columnClass' => 'col-12 col-lg-3',
        'validationRules' => $filterRules['weight'] ?? [],
    ],
    [
        'name' => 'created_at',
        'label' => Translate::t('Creato il'),
        'widget' => 'dateFilter',
        'placeholder' => 'YYYY-MM-DD',
        'icon' => 'fa-regular fa-calendar',
        'columnClass' => 'col-12 col-lg-3',
        'validationRules' => $filterRules['created_at'] ?? [],
    ],
];

$createButton = $canCreate
    ? '<a href="/permission/create?_return=' . rawurlencode($currentUrl) . '" class="btn btn-primary btn-shadow btn-sm">'
        . '<i class="fa-solid fa-plus me-1"></i>' . Html::encode(Translate::t('Nuovo permesso'))
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
$permissionModals = [
    $filterModalId => FilterModal::render(
        id: $filterModalId,
        title: Translate::t('Filtri permessi'),
        action: '/permission',
        filters: $filters,
        fields: $filterFields,
        variant: 'info',
    ),
];

$grid = Grid::render(
    title: Translate::t('Elenco permessi'),
    reader: $reader,
    toolbar: $toolbar,
    variant: 'info',
    urlCreator: $gridUrlCreator,
    columns: [
        new DataColumn(
            property: 'name',
            header: Translate::t('Nome'),
            bodyAttributes: ['class' => 'text-wrap'],
            content: static function (array|object $row): string {
                $permission = new PermissionPresenter($row);

                return sprintf(
                    '<div class="app-task-cell"><div class="app-task-cell__title">%s</div><div class="app-task-cell__meta">%s</div></div>',
                    htmlspecialchars($permission->name(), ENT_QUOTES, 'UTF-8'),
                    Html::encode(Translate::t('Permesso #{id}', ['id' => (int) $permission->id()])),
                );
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'code',
            header: Translate::t('Codice'),
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                $permission = new PermissionPresenter($row);

                return '<code class="small">' . htmlspecialchars($permission->code(), ENT_QUOTES, 'UTF-8') . '</code>';
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'group_name',
            header: Translate::t('Gruppo'),
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                return htmlspecialchars((new PermissionPresenter($row))->groupName(), ENT_QUOTES, 'UTF-8');
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'weight',
            header: Translate::t('Peso'),
            bodyAttributes: ['class' => 'align-middle'],
            content: static fn(array|object $row): string => (string) (new PermissionPresenter($row))->weight(),
        ),
        new DataColumn(
            property: 'created_at',
            header: Translate::t('Creato il'),
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                return (new PermissionPresenter($row))->createdAt();
            },
        ),
        new DataColumn(
            header: Translate::t('Azioni'),
            withSorting: false,
            headerAttributes: ['style' => 'width: 10rem;'],
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row) use ($csrf, $currentUrl, &$permissionModals, $canView, $canUpdate, $canDelete): string {
                $permission = new PermissionPresenter($row);
                $id = (int) $permission->id();
                $deleteModalId = 'permission-delete-modal-' . $id;
                $buttons = [];

                if ($canView) {
                    $buttons[] = CrudActions::viewLink(
                        '/permission/view/' . $id . '?_return=' . rawurlencode($currentUrl),
                    );
                }

                if ($canUpdate) {
                    $buttons[] = CrudActions::updateLink(
                        '/permission/update/' . $id . '?_return=' . rawurlencode($currentUrl),
                    );
                }

                if ($canDelete) {
                    $buttons[] = CrudActions::deleteTrigger($deleteModalId);
                    $deleteModalBody = CrudActions::deleteBody(
                        Translate::t('Stai eliminando il permesso {name}. Dopo la conferma il record non sara piu recuperabile.', ['name' => '<strong>' . Html::encode($permission->name()) . '</strong>']),
                        [
                            Translate::t('ID record') => '#' . $id,
                            Translate::t('Codice') => '<code>' . Html::encode($permission->code()) . '</code>',
                        ],
                    );

                    $permissionModals[$deleteModalId] = CrudActions::deleteModal(
                        id: $deleteModalId,
                        title: Translate::t('Elimina permesso'),
                        action: '/permission/delete/' . $id,
                        body: $deleteModalBody,
                        csrf: $csrf,
                    );
                }

                return CrudActions::group($buttons, Translate::t('Azioni permesso #{id}', ['id' => $id]));
            },
            encodeContent: false,
        ),
    ],
);

if ($permissionModals !== []) {
    $this->setParameter('pageModals', implode('', $permissionModals));
}

echo $grid;

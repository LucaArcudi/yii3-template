<?php

declare(strict_types=1);

use App\Data\Core\Permission\PermissionPresenter;
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

$this->setTitle('Permessi');
$this->setParameter('pageIcon', 'pe-7s-key');
$this->setParameter('breadcrumbs', [
    ['label' => 'Dashboard', 'url' => '/'],
    ['label' => 'Permessi'],
]);

$filterModalId = 'permission-filter-modal';
$filterFields = [
    [
        'name' => 'name',
        'label' => 'Nome',
        'widget' => 'textFilter',
        'placeholder' => 'Cerca per nome',
        'icon' => 'fa-solid fa-magnifying-glass',
        'columnClass' => 'col-12 col-lg-6',
        'validationRules' => $filterRules['name'] ?? [],
    ],
    [
        'name' => 'code',
        'label' => 'Codice',
        'widget' => 'textFilter',
        'placeholder' => 'Es. ACCESS',
        'icon' => 'fa-solid fa-key',
        'columnClass' => 'col-12 col-lg-6',
        'validationRules' => $filterRules['code'] ?? [],
    ],
    [
        'name' => 'group_name',
        'label' => 'Gruppo',
        'widget' => 'textFilter',
        'placeholder' => 'Cerca per gruppo',
        'icon' => 'fa-solid fa-layer-group',
        'columnClass' => 'col-12 col-lg-6',
        'validationRules' => $filterRules['group_name'] ?? [],
    ],
    [
        'name' => 'weight',
        'label' => 'Peso',
        'widget' => 'textFilter',
        'placeholder' => '1',
        'icon' => 'fa-solid fa-weight-hanging',
        'columnClass' => 'col-12 col-lg-3',
        'validationRules' => $filterRules['weight'] ?? [],
    ],
    [
        'name' => 'created_at',
        'label' => 'Creato il',
        'widget' => 'dateFilter',
        'placeholder' => 'YYYY-MM-DD',
        'icon' => 'fa-regular fa-calendar',
        'columnClass' => 'col-12 col-lg-3',
        'validationRules' => $filterRules['created_at'] ?? [],
    ],
];

$createButton = $canCreate
    ? '<a href="/permission/create?_return=' . rawurlencode($currentUrl) . '" class="btn btn-primary btn-shadow btn-sm">'
        . '<i class="fa-solid fa-plus me-1"></i>Nuovo permesso'
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
        title: 'Filtri permessi',
        action: '/permission',
        filters: $filters,
        fields: $filterFields,
        variant: 'info',
    ),
];

$grid = Grid::render(
    title: 'Elenco permessi',
    reader: $reader,
    toolbar: $toolbar,
    variant: 'info',
    urlCreator: $gridUrlCreator,
    columns: [
        new DataColumn(
            property: 'name',
            header: 'Nome',
            bodyAttributes: ['class' => 'text-wrap'],
            content: static function (array|object $row): string {
                $permission = new PermissionPresenter($row);

                return sprintf(
                    '<div class="app-task-cell"><div class="app-task-cell__title">%s</div><div class="app-task-cell__meta">Permesso #%d</div></div>',
                    htmlspecialchars($permission->name(), ENT_QUOTES, 'UTF-8'),
                    (int) $permission->id(),
                );
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'code',
            header: 'Codice',
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                $permission = new PermissionPresenter($row);

                return '<code class="small">' . htmlspecialchars($permission->code(), ENT_QUOTES, 'UTF-8') . '</code>';
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'group_name',
            header: 'Gruppo',
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                return htmlspecialchars((new PermissionPresenter($row))->groupName(), ENT_QUOTES, 'UTF-8');
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'weight',
            header: 'Peso',
            bodyAttributes: ['class' => 'align-middle'],
            content: static fn(array|object $row): string => (string) (new PermissionPresenter($row))->weight(),
        ),
        new DataColumn(
            property: 'created_at',
            header: 'Creato il',
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                return (new PermissionPresenter($row))->createdAt();
            },
        ),
        new DataColumn(
            header: 'Azioni',
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
                        'Stai eliminando il permesso <strong>' . Html::encode($permission->name()) . '</strong>. Dopo la conferma il record non sara piu recuperabile.',
                        [
                            'ID record' => '#' . $id,
                            'Codice' => '<code>' . Html::encode($permission->code()) . '</code>',
                        ],
                    );

                    $permissionModals[$deleteModalId] = CrudActions::deleteModal(
                        id: $deleteModalId,
                        title: 'Elimina permesso',
                        action: '/permission/delete/' . $id,
                        body: $deleteModalBody,
                        csrf: $csrf,
                    );
                }

                return CrudActions::group($buttons, 'Azioni permesso #' . $id);
            },
            encodeContent: false,
        ),
    ],
);

if ($permissionModals !== []) {
    $this->setParameter('pageModals', implode('', $permissionModals));
}

echo $grid;

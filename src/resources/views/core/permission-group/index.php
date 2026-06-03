<?php

declare(strict_types=1);

use App\Data\Core\Permission\PermissionGroupPresenter;
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

$this->setTitle('Gruppi permessi');
$this->setParameter('pageIcon', 'fa-solid fa-layer-group');
$this->setParameter('breadcrumbs', [
    ['label' => 'Dashboard', 'url' => '/'],
    ['label' => 'Gruppi permessi'],
]);

$filterModalId = 'permission-group-filter-modal';
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
        'placeholder' => 'Es. USER',
        'icon' => 'fa-solid fa-key',
        'columnClass' => 'col-12 col-lg-6',
        'validationRules' => $filterRules['code'] ?? [],
    ],
];

$createButton = $canCreate
    ? '<a href="/permission-group/create?_return=' . rawurlencode($currentUrl) . '" class="btn btn-primary btn-shadow btn-sm">'
        . '<i class="fa-solid fa-plus me-1"></i>Nuovo gruppo'
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
$groupModals = [
    $filterModalId => FilterModal::render(
        id: $filterModalId,
        title: 'Filtri gruppi permessi',
        action: '/permission-group',
        filters: $filters,
        fields: $filterFields,
        variant: 'info',
    ),
];

$grid = Grid::render(
    title: 'Elenco gruppi permessi',
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
                $group = new PermissionGroupPresenter((array) $row);

                return sprintf(
                    '<div class="app-task-cell"><div class="app-task-cell__title">%s</div><div class="app-task-cell__meta">Gruppo #%d</div></div>',
                    htmlspecialchars($group->name(), ENT_QUOTES, 'UTF-8'),
                    (int) $group->id(),
                );
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'code',
            header: 'Codice',
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                return '<code class="small">' . htmlspecialchars((new PermissionGroupPresenter((array) $row))->code(), ENT_QUOTES, 'UTF-8') . '</code>';
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'created_at',
            header: 'Creato il',
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                return (new PermissionGroupPresenter((array) $row))->createdAt();
            },
        ),
        new DataColumn(
            header: 'Azioni',
            withSorting: false,
            headerAttributes: ['style' => 'width: 10rem;'],
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row) use ($csrf, $currentUrl, &$groupModals, $canView, $canUpdate, $canDelete): string {
                $group = new PermissionGroupPresenter((array) $row);
                $id = (int) $group->id();
                $deleteModalId = 'permission-group-delete-modal-' . $id;
                $buttons = [];

                if ($canView) {
                    $buttons[] = CrudActions::viewLink(
                        '/permission-group/view/' . $id . '?_return=' . rawurlencode($currentUrl),
                    );
                }

                if ($canUpdate) {
                    $buttons[] = CrudActions::updateLink(
                        '/permission-group/update/' . $id . '?_return=' . rawurlencode($currentUrl),
                    );
                }

                if ($canDelete) {
                    $buttons[] = CrudActions::deleteTrigger($deleteModalId);
                    $deleteModalBody = CrudActions::deleteBody(
                        'Stai eliminando il gruppo <strong>' . Html::encode($group->name()) . '</strong>. Dopo la conferma il record non sara piu recuperabile.',
                        [
                            'ID record' => '#' . $id,
                            'Codice' => '<code>' . Html::encode($group->code()) . '</code>',
                        ],
                    );

                    $groupModals[$deleteModalId] = CrudActions::deleteModal(
                        id: $deleteModalId,
                        title: 'Elimina gruppo permessi',
                        action: '/permission-group/delete/' . $id,
                        body: $deleteModalBody,
                        csrf: $csrf,
                    );
                }

                return CrudActions::group($buttons, 'Azioni gruppo permessi #' . $id);
            },
            encodeContent: false,
        ),
    ],
);

if ($groupModals !== []) {
    $this->setParameter('pageModals', implode('', $groupModals));
}

echo $grid;

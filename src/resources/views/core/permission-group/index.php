<?php

declare(strict_types=1);

use App\Data\Core\Permission\PermissionGroupPresenter;
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

$this->setTitle(Translate::t('Gruppi permessi'));
$this->setParameter('pageIcon', 'fa-solid fa-layer-group');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Gruppi permessi')],
]);

$filterModalId = 'permission-group-filter-modal';
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
        'placeholder' => Translate::t('Es. USER'),
        'icon' => 'fa-solid fa-key',
        'columnClass' => 'col-12 col-lg-6',
        'validationRules' => $filterRules['code'] ?? [],
    ],
];

$createButton = $canCreate
    ? '<a href="/permission-group/create?_return=' . rawurlencode($currentUrl) . '" class="btn btn-primary btn-shadow btn-sm">'
        . '<i class="fa-solid fa-plus me-1"></i>' . Html::encode(Translate::t('Nuovo gruppo'))
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
        title: Translate::t('Filtri gruppi permessi'),
        action: '/permission-group',
        filters: $filters,
        fields: $filterFields,
        variant: 'info',
    ),
];

$grid = Grid::render(
    title: Translate::t('Elenco gruppi permessi'),
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
                $group = new PermissionGroupPresenter((array) $row);

                return sprintf(
                    '<div class="app-task-cell"><div class="app-task-cell__title">%s</div><div class="app-task-cell__meta">%s</div></div>',
                    htmlspecialchars($group->name(), ENT_QUOTES, 'UTF-8'),
                    Html::encode(Translate::t('Gruppo #{id}', ['id' => (int) $group->id()])),
                );
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'code',
            header: Translate::t('Codice'),
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                return '<code class="small">' . htmlspecialchars((new PermissionGroupPresenter((array) $row))->code(), ENT_QUOTES, 'UTF-8') . '</code>';
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'created_at',
            header: Translate::t('Creato il'),
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                return (new PermissionGroupPresenter((array) $row))->createdAt();
            },
        ),
        new DataColumn(
            header: Translate::t('Azioni'),
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
                        Translate::t('Stai eliminando il gruppo {name}. Dopo la conferma il record non sara piu recuperabile.', ['name' => '<strong>' . Html::encode($group->name()) . '</strong>']),
                        [
                            Translate::t('ID record') => '#' . $id,
                            Translate::t('Codice') => '<code>' . Html::encode($group->code()) . '</code>',
                        ],
                    );

                    $groupModals[$deleteModalId] = CrudActions::deleteModal(
                        id: $deleteModalId,
                        title: Translate::t('Elimina gruppo permessi'),
                        action: '/permission-group/delete/' . $id,
                        body: $deleteModalBody,
                        csrf: $csrf,
                    );
                }

                return CrudActions::group($buttons, Translate::t('Azioni gruppo permessi #{id}', ['id' => $id]));
            },
            encodeContent: false,
        ),
    ],
);

if ($groupModals !== []) {
    $this->setParameter('pageModals', implode('', $groupModals));
}

echo $grid;

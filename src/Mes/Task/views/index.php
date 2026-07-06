<?php

declare(strict_types=1);

use App\Mes\Task\TaskPresenter;
use App\Mes\Task\TaskEntity;
use App\Helpers\Translate;
use App\Widgets\Crud\CrudActions;
use App\Widgets\DataView\CardList;
use App\Widgets\DataView\Grid;
use App\Widgets\Filters\FilterBar;
use App\Widgets\Filters\FilterModal;
use App\Widgets\Tabs;
use Yiisoft\Data\Db\QueryDataReader;
use Yiisoft\Html\Html;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\View\Renderer\Csrf;

/** @var QueryDataReader $gridReader */
/** @var QueryDataReader $cardReader */
/** @var array $gridFilters */
/** @var array $cardFilters */
/** @var string $display */
/** @var array $filterRules */
/** @var Csrf $csrf */
/** @var callable $gridUrlCreator */
/** @var callable $cardUrlCreator */
/** @var string $gridTabUrl */
/** @var string $cardTabUrl */
/** @var string $currentUrl */
/** @var bool $canCreate */
/** @var bool $canView */
/** @var bool $canUpdate */
/** @var bool $canDelete */

$this->setTitle(Translate::t('Tasks'));
$this->setParameter('pageIcon', 'pe-7s-note2');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Tasks')],
]);

$filterModalId = 'task-filter-modal';
$display = $display === 'cards' ? 'cards' : 'grid';
$page = max(1, (int) ($cardFilters['page'] ?? 1));
$filterFields = [
    [
        'name' => 'title',
        'label' => Translate::t('Titolo'),
        'widget' => 'textFilter',
        'placeholder' => Translate::t('Cerca per titolo'),
        'icon' => 'fa-solid fa-magnifying-glass',
        'columnClass' => 'col-12 col-lg-6',
        'validationRules' => $filterRules['title'] ?? [],
    ],
    [
        'name' => 'description',
        'label' => Translate::t('Descrizione'),
        'widget' => 'textFilter',
        'placeholder' => Translate::t('Parola chiave'),
        'icon' => 'fa-solid fa-align-left',
        'columnClass' => 'col-12 col-lg-6',
        'validationRules' => $filterRules['description'] ?? [],
    ],
    [
        'name' => 'status',
        'label' => Translate::t('Stato'),
        'widget' => 'multiSelectFilter',
        'options' => TaskEntity::statusOptions(),
        'placeholder' => Translate::t('Tutti gli stati'),
        'icon' => 'fa-solid fa-signal',
        'columnClass' => 'col-12 col-lg-4',
        'validationRules' => $filterRules['status'] ?? [],
    ],
    [
        'name' => 'start_date',
        'label' => Translate::t('Data inizio'),
        'widget' => 'dateFilter',
        'icon' => 'fa-regular fa-calendar',
        'columnClass' => 'col-12 col-lg-4',
        'validationRules' => $filterRules['start_date'] ?? [],
    ],
    [
        'name' => 'end_date',
        'label' => Translate::t('Data fine'),
        'widget' => 'dateFilter',
        'icon' => 'fa-regular fa-calendar-check',
        'columnClass' => 'col-12 col-lg-4',
        'validationRules' => $filterRules['end_date'] ?? [],
    ],
];

$createButton = $canCreate
    ? '<a href="/task/create?_return=' . rawurlencode($currentUrl) . '" class="btn btn-primary btn-shadow btn-sm">'
        . '<i class="fa-solid fa-plus me-1"></i>' . Html::encode(Translate::t('Nuova task'))
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
    FilterModal::button($filterModalId, FilterModal::activeCount($gridFilters, $filterFields)),
    $createButton,
);
$taskModals = [
    $filterModalId => FilterModal::render(
        id: $filterModalId,
        title: Translate::t('Filtri task'),
        action: '/task',
        filters: $gridFilters,
        fields: $filterFields,
        variant: 'primary',
        icon: 'fa-solid fa-sliders',
    ),
];

$grid = Grid::render(
    title: Translate::t('Elenco task'),
    reader: $gridReader,
    toolbar: $toolbar,
    variant: 'primary',
    urlCreator: $gridUrlCreator,
    queryParameters: $gridFilters,
    columns: [
        new DataColumn(
            property: 'title',
            header: Translate::t('Titolo'),
            bodyAttributes: ['class' => 'text-wrap'],
            content: static function (array|object $row): string {
                $task = new TaskPresenter($row);

                return sprintf(
                    '<div class="app-task-cell"><div class="app-task-cell__title">%s</div><div class="app-task-cell__meta">%s</div></div>',
                    htmlspecialchars($task->title(), ENT_QUOTES, 'UTF-8'),
                    Html::encode(Translate::t('Task #{id}', ['id' => (int) $task->id()])),
                );
            },
            encodeContent: false,
        ),

        new DataColumn(
            property: 'description',
            header: Translate::t('Descrizione'),
            headerAttributes: ['style' => 'width: 32%;'],
            bodyAttributes: ['class' => 'text-wrap'],
            content: static function (array|object $row): string {
                $task = new TaskPresenter($row);
                $description = trim($task->description());

                if ($description === '') {
                    return '<span class="text-muted">' . Html::encode(Translate::t('Nessuna descrizione disponibile')) . '</span>';
                }

                return '<div class="app-task-cell__excerpt">'
                    . htmlspecialchars($task->excerpt(120), ENT_QUOTES, 'UTF-8')
                    . '</div>';
            },
            encodeContent: false,
        ),

        new DataColumn(
            property: 'status',
            header: Translate::t('Stato'),
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row): string {
                return (new TaskPresenter($row))->statusBadge();
            },
            encodeContent: false,
        ),
        new DataColumn(
            property: 'start_date',
            header: Translate::t('Inizio'),
            bodyAttributes: ['class' => 'align-middle text-nowrap'],
            content: static fn(array|object $row): string => (new TaskPresenter($row))->startDate(),
        ),
        new DataColumn(
            property: 'end_date',
            header: Translate::t('Fine'),
            bodyAttributes: ['class' => 'align-middle text-nowrap'],
            content: static fn(array|object $row): string => (new TaskPresenter($row))->endDate(),
        ),
        new DataColumn(
            header: Translate::t('Azioni'),
            withSorting: false,
            headerAttributes: ['style' => 'width: 10rem;'],
            bodyAttributes: ['class' => 'align-middle'],
            content: static function (array|object $row) use ($csrf, $currentUrl, &$taskModals, $canView, $canUpdate, $canDelete): string {
                $task = new TaskPresenter($row);
                $id = (int) $task->id();
                $deleteModalId = 'task-delete-modal-' . $id;
                $buttons = [];

                if ($canView) {
                    $buttons[] = CrudActions::viewLink(
                        '/task/view/' . $id . '?_return=' . rawurlencode($currentUrl),
                    );
                }

                if ($canUpdate) {
                    $buttons[] = CrudActions::updateLink(
                        '/task/update/' . $id . '?_return=' . rawurlencode($currentUrl),
                    );
                }

                if ($canDelete) {
                    $buttons[] = CrudActions::deleteTrigger($deleteModalId);
                    $deleteModalBody = CrudActions::deleteBody(
                        Translate::t('Stai eliminando la task {title}. Dopo la conferma il record non sara piu recuperabile.', ['title' => '<strong>' . Html::encode($task->title()) . '</strong>']),
                        [
                            Translate::t('ID record') => '#' . $id,
                            Translate::t('Stato corrente') => $task->statusBadge(),
                        ],
                    );

                    $taskModals[$deleteModalId] = CrudActions::deleteModal(
                        id: $deleteModalId,
                        title: Translate::t('Elimina task'),
                        action: '/task/delete/' . $id,
                        body: $deleteModalBody,
                        csrf: $csrf,
                    );
                }

                return CrudActions::group($buttons, Translate::t('Azioni task #{id}', ['id' => $id]));
            },
            encodeContent: false,
        ),
    ],
);

$cardFilterBar = FilterBar::render(
    view: $this,
    action: '/task',
    filters: $cardFilters,
    fields: $filterFields,
    collapsed: true,
    title: Translate::t('Filtri vista card'),
    icon: 'fa-solid fa-filter',
);

$cardList = $cardFilterBar . CardList::render(
    title: Translate::t('Task a card'),
    reader: $cardReader,
    urlCreator: $cardUrlCreator,
    page: $page,
    pageSize: 5,
    variant: 'info',
    itemRenderer: static function (array|object $row) use ($currentUrl, $canView, $canUpdate): string {
        $task = new TaskPresenter($row);
        $id = (int) $task->id();
        $actions = [];

        if ($canView) {
            $actions[] = (string) Html::a(
                (string) Html::i('', ['class' => 'fa-solid fa-eye me-1']) . Html::encode(Translate::t('Apri')),
                '/task/view/' . $id . '?_return=' . rawurlencode($currentUrl),
                ['class' => ['btn', 'btn-sm', 'btn-outline-primary']],
            )->encode(false);
        }

        if ($canUpdate) {
            $actions[] = (string) Html::a(
                (string) Html::i('', ['class' => 'fa-solid fa-pen-to-square me-1']) . Html::encode(Translate::t('Modifica')),
                '/task/update/' . $id . '?_return=' . rawurlencode($currentUrl),
                ['class' => ['btn', 'btn-sm', 'btn-outline-warning']],
            )->encode(false);
        }

        $description = trim($task->description());

        return (string) Html::tag(
            'article',
            (string) Html::div(
                (string) Html::div(
                    (string) Html::h3($task->title(), ['class' => 'app-task-card__title'])
                    . (string) Html::div(Translate::t('Task #{id}', ['id' => $id]), ['class' => 'app-task-card__meta']),
                    ['class' => 'app-task-card__heading'],
                )->encode(false)
                . $task->statusBadge(),
                ['class' => 'app-task-card__header'],
            )->encode(false)
            . (string) Html::p(
                $description !== '' ? Html::encode($task->excerpt(180)) : Html::encode(Translate::t('Nessuna descrizione disponibile')),
                ['class' => ['app-task-card__description', $description === '' ? 'text-muted' : null]],
            )->encode(false)
            . (string) Html::div(
                (string) Html::span((string) Html::i('', ['class' => 'fa-regular fa-calendar me-1']) . $task->startDate(), ['class' => 'app-task-card__date'])->encode(false)
                . (string) Html::span((string) Html::i('', ['class' => 'fa-regular fa-calendar-check me-1']) . $task->endDate(), ['class' => 'app-task-card__date'])->encode(false),
                ['class' => 'app-task-card__dates'],
            )->encode(false)
            . (string) Html::div(
                implode('', $actions) ?: (string) Html::span('-', ['class' => 'text-muted']),
                ['class' => 'app-task-card__actions'],
            )->encode(false),
            ['class' => 'app-task-card'],
        )->encode(false);
    },
);

if ($taskModals !== []) {
    $this->setParameter('pageModals', implode('', $taskModals));
}

echo Tabs::render(
    [
        [
            'id' => 'grid',
            'label' => 'Grid',
            'icon' => 'fa-solid fa-table-list',
            'content' => $grid,
            'url' => $gridTabUrl,
            'active' => $display === 'grid',
        ],
        [
            'id' => 'cards',
            'label' => 'Cards',
            'icon' => 'fa-regular fa-address-card',
            'content' => $cardList,
            'url' => $cardTabUrl,
            'active' => $display === 'cards',
        ],
    ],
    'task-index-tabs',
);

$this->registerJs(
    <<<'JS'
        (function () {
            const tabs = document.getElementById('task-index-tabs');

            if (!tabs) {
                return;
            }

            const currentReturnUrl = () => window.location.pathname + window.location.search;
            const syncReturnLinks = () => {
                tabs.querySelectorAll('a[href*="_return="]').forEach((link) => {
                    const href = link.getAttribute('href');

                    if (!href) {
                        return;
                    }

                    const url = new URL(href, window.location.origin);
                    url.searchParams.set('_return', currentReturnUrl());
                    link.setAttribute('href', url.pathname + url.search + url.hash);
                });
            };

            tabs.querySelectorAll('[data-tab-url]').forEach((trigger) => {
                trigger.addEventListener('shown.bs.tab', () => {
                    const url = trigger.getAttribute('data-tab-url');

                    if (url) {
                        window.location.assign(url);
                        return;
                    }

                    syncReturnLinks();
                });
            });

            const currentUrl = new URL(window.location.href);
            const activeTrigger = tabs.querySelector('[data-tab-url].active');

            if (!currentUrl.searchParams.has('display') && activeTrigger) {
                const display = activeTrigger.getAttribute('data-tab-id') === 'cards' ? 'cards' : 'grid';
                currentUrl.searchParams.set('display', display);

                window.history.replaceState(
                    window.history.state,
                    '',
                    currentUrl.pathname + currentUrl.search + currentUrl.hash
                );
            }

            syncReturnLinks();
        })();
    JS,
);

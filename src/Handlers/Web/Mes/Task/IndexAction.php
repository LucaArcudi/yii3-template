<?php

declare(strict_types=1);

namespace App\Handlers\Web\Mes\Task;

use App\Data\Mes\Task\TaskFilter;
use App\Data\Mes\Task\TaskPolicy;
use App\Data\Mes\Task\TaskReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

use function array_merge;
use function parse_str;
use function parse_url;

final readonly class IndexAction
{
    private const GRID_DISPLAY = 'grid';
    private const CARD_DISPLAY = 'cards';
    private const GRID_STATE_KEY = 'task.index.grid.filters';
    private const CARD_STATE_KEY = 'task.index.cards.filters';

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private TaskReader $taskReader,
        private TaskFilter $taskFilter,
        private TaskPolicy $taskPolicy,
        private UrlGeneratorInterface $urlGenerator,
        private WebActionService $webAction,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $this->taskFilter->validate($request->getQueryParams());
        $display = ($query['display'] ?? null) === self::CARD_DISPLAY ? self::CARD_DISPLAY : self::GRID_DISPLAY;

        if (!$this->taskPolicy->canAccess()) {
            return $this->webAction->forbidden();
        }

        $currentUrl = $this->webAction->rememberCurrent('task.index', $request);
        $activeQuery = $this->tabQuery($query, $display);
        $this->rememberTabQuery($display, $this->withoutPagination($activeQuery));

        $gridQuery = $display === self::GRID_DISPLAY
            ? $activeQuery
            : $this->rememberedTabQuery(self::GRID_DISPLAY);
        $cardQuery = $display === self::CARD_DISPLAY
            ? $activeQuery
            : $this->rememberedTabQuery(self::CARD_DISPLAY);

        $gridReader = $this->taskReader->getIndex(
            filters: $gridQuery,
            sort: $this->webAction->sort($gridQuery, '-id'),
        );

        $cardReader = $this->taskReader->getIndex(
            filters: $cardQuery,
            sort: '-id',
        );

        $gridUrlCreator = $this->webAction->gridUrlCreator(
            'task/index',
            $gridQuery,
            ['display' => self::GRID_DISPLAY],
        );

        $cardUrlCreator = function (int $page) use ($cardQuery): string {
            $mergedQueryParameters = array_merge($cardQuery, [
                'display' => self::CARD_DISPLAY,
                'page' => $page,
            ]);

            return $this->urlGenerator->generate(
                'task/index',
                [],
                $this->webAction->query($mergedQueryParameters)
            );
        };

        return $this->viewRenderer->render('mes/task/index', [
            'gridReader' => $gridReader,
            'cardReader' => $cardReader,
            'gridFilters' => $gridQuery,
            'cardFilters' => $cardQuery,
            'display' => $display,
            'filterRules' => $this->taskFilter->getFilterRules(),
            'gridUrlCreator' => $gridUrlCreator,
            'cardUrlCreator' => $cardUrlCreator,
            'gridTabUrl' => $this->tabUrl(self::GRID_DISPLAY, $gridQuery),
            'cardTabUrl' => $this->tabUrl(self::CARD_DISPLAY, $cardQuery),
            'currentUrl' => $currentUrl,
            'canCreate' => $this->taskPolicy->canCreate(),
            'canView' => $this->taskPolicy->canView(),
            'canUpdate' => $this->taskPolicy->canUpdate(),
            'canDelete' => $this->taskPolicy->canDelete(),
        ]);
    }

    private function rememberedTabQuery(string $display): array
    {
        $url = $this->webAction->previous($this->stateKey($display), '');
        $parts = $url !== '' ? parse_url($url) : false;
        $parameters = [];

        if ($parts !== false && ($parts['query'] ?? '') !== '') {
            parse_str((string) $parts['query'], $parameters);
        }

        return $this->withoutPagination(
            $this->tabQuery($this->taskFilter->validate($parameters), $display),
        );
    }

    private function rememberTabQuery(string $display, array $query): void
    {
        $this->webAction->remember($this->stateKey($display), $this->url($query));
    }

    private function tabQuery(array $query, string $display): array
    {
        $display = $display === self::CARD_DISPLAY ? self::CARD_DISPLAY : self::GRID_DISPLAY;
        $query['display'] = $display;

        if ($display === self::CARD_DISPLAY) {
            unset($query['sort']);
        }

        return $query;
    }

    private function withoutPagination(array $query): array
    {
        unset($query['page'], $query['previous-page']);

        return $query;
    }

    private function tabUrl(string $display, array $query): string
    {
        return $this->url($this->withoutPagination($this->tabQuery($query, $display)));
    }

    private function url(array $query): string
    {
        return $this->urlGenerator->generate('task/index', [], $this->webAction->query($query));
    }

    private function stateKey(string $display): string
    {
        return $display === self::CARD_DISPLAY ? self::CARD_STATE_KEY : self::GRID_STATE_KEY;
    }
}

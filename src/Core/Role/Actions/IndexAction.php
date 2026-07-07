<?php

declare(strict_types=1);

namespace App\Core\Role\Actions;

use App\Core\Role\RoleFilter;
use App\Core\Role\RolePolicy;
use App\Core\Role\RoleReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private RoleReader $roleReader,
        private RoleFilter $roleFilter,
        private RolePolicy $rolePolicy,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/Role/views');
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $this->roleFilter->validate($request->getQueryParams());

        if (!$this->rolePolicy->canAccess()) {
            return $this->webAction->forbidden();
        }

        $currentUrl = $this->webAction->rememberCurrent('role.index', $request);

        $reader = $this->roleReader->getIndex(
            filters: $query,
            sort: $this->webAction->sort($query, '-id'),
        );

        return $this->viewRenderer->render('index', [
            'reader' => $reader,
            'filters' => $query,
            'filterRules' => $this->roleFilter->getFilterRules(),
            'gridUrlCreator' => $this->webAction->gridUrlCreator('role/index', $query),
            'currentUrl' => $currentUrl,
            'canCreate' => $this->rolePolicy->canCreate(),
            'canView' => $this->rolePolicy->canView(),
            'canUpdate' => $this->rolePolicy->canUpdate(),
            'canDelete' => $this->rolePolicy->canDelete(),
        ]);
    }
}

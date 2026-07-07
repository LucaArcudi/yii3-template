<?php

declare(strict_types=1);

namespace App\Core\Permission\Actions;

use App\Core\Permission\PermissionFilter;
use App\Core\Permission\PermissionPolicy;
use App\Core\Permission\PermissionReader;
use App\Shared\Services\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private PermissionReader $permissionReader,
        private PermissionFilter $permissionFilter,
        private PermissionPolicy $permissionPolicy,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/Permission/views');
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $this->permissionFilter->validate($request->getQueryParams());

        if (!$this->permissionPolicy->canAccess()) {
            return $this->webAction->forbidden();
        }

        $currentUrl = $this->webAction->rememberCurrent('permission.index', $request);

        $reader = $this->permissionReader->getIndex(
            filters: $query,
            sort: $this->webAction->sort($query, '-id'),
        );

        return $this->viewRenderer->render('index', [
            'reader' => $reader,
            'filters' => $query,
            'filterRules' => $this->permissionFilter->getFilterRules(),
            'gridUrlCreator' => $this->webAction->gridUrlCreator('permission/index', $query),
            'currentUrl' => $currentUrl,
            'canCreate' => $this->permissionPolicy->canCreate(),
            'canView' => $this->permissionPolicy->canView(),
            'canUpdate' => $this->permissionPolicy->canUpdate(),
            'canDelete' => $this->permissionPolicy->canDelete(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Core\PermissionGroup\Actions;

use App\Core\PermissionGroup\PermissionGroupFilter;
use App\Core\PermissionGroup\PermissionGroupPolicy;
use App\Core\PermissionGroup\PermissionGroupReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private PermissionGroupReader $permissionGroupReader,
        private PermissionGroupFilter $permissionGroupFilter,
        private PermissionGroupPolicy $permissionGroupPolicy,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/PermissionGroup/views');
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $this->permissionGroupFilter->validate($request->getQueryParams());

        if (!$this->permissionGroupPolicy->canAccess()) {
            return $this->webAction->forbidden();
        }

        $currentUrl = $this->webAction->rememberCurrent('permission-group.index', $request);

        $reader = $this->permissionGroupReader->getIndex(
            filters: $query,
            sort: $this->webAction->sort($query, '-id'),
        );

        return $this->viewRenderer->render('index', [
            'reader' => $reader,
            'filters' => $query,
            'filterRules' => $this->permissionGroupFilter->getFilterRules(),
            'gridUrlCreator' => $this->webAction->gridUrlCreator('permission-group/index', $query),
            'currentUrl' => $currentUrl,
            'canCreate' => $this->permissionGroupPolicy->canCreate(),
            'canView' => $this->permissionGroupPolicy->canView(),
            'canUpdate' => $this->permissionGroupPolicy->canUpdate(),
            'canDelete' => $this->permissionGroupPolicy->canDelete(),
        ]);
    }
}

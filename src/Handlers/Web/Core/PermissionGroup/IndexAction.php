<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\PermissionGroup;

use App\Data\Core\Permission\PermissionGroupFilter;
use App\Data\Core\Permission\PermissionGroupPolicy;
use App\Data\Core\Permission\PermissionGroupReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private PermissionGroupReader $permissionGroupReader,
        private PermissionGroupFilter $permissionGroupFilter,
        private PermissionGroupPolicy $permissionGroupPolicy,
        private WebActionService $webAction,
    ) {}

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

        return $this->viewRenderer->render('core/permission-group/index', [
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

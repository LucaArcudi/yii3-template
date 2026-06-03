<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\Permission;

use App\Data\Core\Permission\PermissionFilter;
use App\Data\Core\Permission\PermissionPolicy;
use App\Data\Core\Permission\PermissionReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private PermissionReader $permissionReader,
        private PermissionFilter $permissionFilter,
        private PermissionPolicy $permissionPolicy,
        private WebActionService $webAction,
    ) {
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

        return $this->viewRenderer->render('core/permission/index', [
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

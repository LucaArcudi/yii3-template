<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\Role;

use App\Data\Core\Role\RoleFilter;
use App\Data\Core\Role\RolePolicy;
use App\Data\Core\Role\RoleReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private RoleReader $roleReader,
        private RoleFilter $roleFilter,
        private RolePolicy $rolePolicy,
        private WebActionService $webAction,
    ) {
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

        return $this->viewRenderer->render('core/role/index', [
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

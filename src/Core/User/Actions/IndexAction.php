<?php

declare(strict_types=1);

namespace App\Core\User\Actions;

use App\Core\Role\RoleRepository;
use App\Core\User\UserFilter;
use App\Core\User\UserPolicy;
use App\Core\User\UserReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private UserReader $userReader,
        private UserFilter $userFilter,
        private RoleRepository $roleRepository,
        private UserPolicy $userPolicy,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/User/views');
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->userPolicy->canAccess()) {
            return $this->webAction->forbidden();
        }

        $roleOptions = $this->roleRepository->findSelectableOptions();
        $query = $this->userFilter->validate($request->getQueryParams(), array_keys($roleOptions));
        $currentUrl = $this->webAction->rememberCurrent('user.index', $request);

        $reader = $this->userReader->getIndex(
            filters: $query,
            sort: $this->webAction->sort($query, '-id'),
        );

        return $this->viewRenderer->render('index', [
            'reader' => $reader,
            'filters' => $query,
            'filterRules' => $this->userFilter->getFilterRules(),
            'roleOptions' => $roleOptions,
            'gridUrlCreator' => $this->webAction->gridUrlCreator('user/index', $query),
            'currentUrl' => $currentUrl,
            'canCreate' => $this->userPolicy->canCreate(),
            'canView' => $this->userPolicy->canView(),
            'canUpdate' => $this->userPolicy->canUpdate(),
            'canDelete' => $this->userPolicy->canDelete(),
        ]);
    }
}

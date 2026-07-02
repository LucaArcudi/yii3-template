<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\User;

use App\Data\Core\Role\RoleRepository;
use App\Data\Core\User\UserFilter;
use App\Data\Core\User\UserPolicy;
use App\Data\Core\User\UserReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class IndexAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UserReader $userReader,
        private UserFilter $userFilter,
        private RoleRepository $roleRepository,
        private UserPolicy $userPolicy,
        private WebActionService $webAction,
    ) {}

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

        return $this->viewRenderer->render('core/user/index', [
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

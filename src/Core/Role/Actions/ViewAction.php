<?php

declare(strict_types=1);

namespace App\Core\Role\Actions;

use App\Core\Permission\PermissionRepository;
use App\Core\Log\LogPolicy;
use App\Core\Log\LogReader;
use App\Core\Role\RolePolicy;
use App\Core\Role\RolePresenter;
use App\Core\Role\RoleReader;
use App\Shared\Helpers\Translate;
use App\Shared\Services\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ViewAction
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private RoleReader $roleReader,
        private PermissionRepository $permissionRepository,
        private RolePolicy $rolePolicy,
        private LogReader $logReader,
        private LogPolicy $logPolicy,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/Role/views');
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')]
        int $id,
    ): ResponseInterface {
        if (!$this->rolePolicy->canView()) {
            return $this->webAction->forbidden();
        }

        $row = $this->roleReader->getView($id);

        if ($row === null) {
            return $this->webAction->notFound(Translate::t('Ruolo non trovato.'));
        }

        $navigation = $this->webAction->viewNavigation('role', $id, $request, '/role');
        $canViewLogs = $this->logPolicy->canAccess();

        return $this->viewRenderer->render('view', [
            'role' => new RolePresenter($row),
            'permissionGroups' => $this->permissionRepository->findGroupedByRoleId($id),
            'logs' => $canViewLogs ? $this->logReader->findByEntity('role', $id) : [],
            'canViewLogs' => $canViewLogs,
            'canUpdate' => $this->rolePolicy->canUpdate(),
            'canDelete' => $this->rolePolicy->canDelete(),
            ...$navigation->parameters(),
        ]);
    }
}

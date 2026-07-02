<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\Role;

use App\Data\Core\Permission\PermissionRepository;
use App\Data\Core\Log\LogPolicy;
use App\Data\Core\Log\LogReader;
use App\Data\Core\Role\RolePolicy;
use App\Data\Core\Role\RolePresenter;
use App\Data\Core\Role\RoleReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ViewAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private RoleReader $roleReader,
        private PermissionRepository $permissionRepository,
        private RolePolicy $rolePolicy,
        private LogReader $logReader,
        private LogPolicy $logPolicy,
        private WebActionService $webAction,
    ) {}

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
            return $this->webAction->notFound('Ruolo non trovato.');
        }

        $navigation = $this->webAction->viewNavigation('role', $id, $request, '/role');
        $canViewLogs = $this->logPolicy->canAccess();

        return $this->viewRenderer->render('core/role/view', [
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

<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\PermissionGroup;

use App\Data\Core\Log\LogPolicy;
use App\Data\Core\Log\LogReader;
use App\Data\Core\Permission\PermissionGroupPresenter;
use App\Data\Core\Permission\PermissionGroupPolicy;
use App\Data\Core\Permission\PermissionGroupReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ViewAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private PermissionGroupReader $permissionGroupReader,
        private PermissionGroupPolicy $permissionGroupPolicy,
        private LogReader $logReader,
        private LogPolicy $logPolicy,
        private WebActionService $webAction,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')] int $id,
    ): ResponseInterface {
        if (!$this->permissionGroupPolicy->canView()) {
            return $this->webAction->forbidden();
        }

        $row = $this->permissionGroupReader->getView($id);

        if ($row === null) {
            return $this->webAction->notFound('Gruppo permessi non trovato.');
        }

        $navigation = $this->webAction->viewNavigation('permission-group', $id, $request, '/permission-group');
        $canViewLogs = $this->logPolicy->canAccess();

        return $this->viewRenderer->render('core/permission-group/view', [
            'group' => new PermissionGroupPresenter($row),
            'logs' => $canViewLogs ? $this->logReader->findByEntity('permission_group', $id) : [],
            'canViewLogs' => $canViewLogs,
            'canUpdate' => $this->permissionGroupPolicy->canUpdate(),
            'canDelete' => $this->permissionGroupPolicy->canDelete(),
            ...$navigation->parameters(),
        ]);
    }
}

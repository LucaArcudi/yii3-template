<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\Permission;

use App\Data\Core\Log\LogPolicy;
use App\Data\Core\Log\LogReader;
use App\Data\Core\Permission\PermissionPolicy;
use App\Data\Core\Permission\PermissionPresenter;
use App\Data\Core\Permission\PermissionReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ViewAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private PermissionReader $permissionReader,
        private PermissionPolicy $permissionPolicy,
        private LogReader $logReader,
        private LogPolicy $logPolicy,
        private WebActionService $webAction,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')] int $id,
    ): ResponseInterface {
        if (!$this->permissionPolicy->canView()) {
            return $this->webAction->forbidden();
        }

        $row = $this->permissionReader->getView($id);

        if ($row === null) {
            return $this->webAction->notFound('Permesso non trovato.');
        }

        $navigation = $this->webAction->viewNavigation('permission', $id, $request, '/permission');
        $canViewLogs = $this->logPolicy->canAccess();

        return $this->viewRenderer->render('core/permission/view', [
            'permission' => new PermissionPresenter($row),
            'logs' => $canViewLogs ? $this->logReader->findByEntity('permission', $id) : [],
            'canViewLogs' => $canViewLogs,
            'canUpdate' => $this->permissionPolicy->canUpdate(),
            'canDelete' => $this->permissionPolicy->canDelete(),
            ...$navigation->parameters(),
        ]);
    }
}

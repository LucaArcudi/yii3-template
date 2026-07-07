<?php

declare(strict_types=1);

namespace App\Core\Permission\Actions;

use App\Core\Log\LogPolicy;
use App\Core\Log\LogReader;
use App\Core\Permission\PermissionPolicy;
use App\Core\Permission\PermissionPresenter;
use App\Core\Permission\PermissionReader;
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
        private PermissionReader $permissionReader,
        private PermissionPolicy $permissionPolicy,
        private LogReader $logReader,
        private LogPolicy $logPolicy,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/Permission/views');
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')]
        int $id,
    ): ResponseInterface {
        if (!$this->permissionPolicy->canView()) {
            return $this->webAction->forbidden();
        }

        $row = $this->permissionReader->getView($id);

        if ($row === null) {
            return $this->webAction->notFound(Translate::t('Permesso non trovato.'));
        }

        $navigation = $this->webAction->viewNavigation('permission', $id, $request, '/permission');
        $canViewLogs = $this->logPolicy->canAccess();

        return $this->viewRenderer->render('view', [
            'permission' => new PermissionPresenter($row),
            'logs' => $canViewLogs ? $this->logReader->findByEntity('permission', $id) : [],
            'canViewLogs' => $canViewLogs,
            'canUpdate' => $this->permissionPolicy->canUpdate(),
            'canDelete' => $this->permissionPolicy->canDelete(),
            ...$navigation->parameters(),
        ]);
    }
}

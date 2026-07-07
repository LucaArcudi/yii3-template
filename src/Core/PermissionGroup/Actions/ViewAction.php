<?php

declare(strict_types=1);

namespace App\Core\PermissionGroup\Actions;

use App\Core\Log\LogPolicy;
use App\Core\Log\LogReader;
use App\Core\PermissionGroup\PermissionGroupPresenter;
use App\Core\PermissionGroup\PermissionGroupPolicy;
use App\Core\PermissionGroup\PermissionGroupReader;
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
        private PermissionGroupReader $permissionGroupReader,
        private PermissionGroupPolicy $permissionGroupPolicy,
        private LogReader $logReader,
        private LogPolicy $logPolicy,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/PermissionGroup/views');
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')]
        int $id,
    ): ResponseInterface {
        if (!$this->permissionGroupPolicy->canView()) {
            return $this->webAction->forbidden();
        }

        $row = $this->permissionGroupReader->getView($id);

        if ($row === null) {
            return $this->webAction->notFound(Translate::t('Gruppo permessi non trovato.'));
        }

        $navigation = $this->webAction->viewNavigation('permission-group', $id, $request, '/permission-group');
        $canViewLogs = $this->logPolicy->canAccess();

        return $this->viewRenderer->render('view', [
            'group' => new PermissionGroupPresenter($row),
            'logs' => $canViewLogs ? $this->logReader->findByEntity('permission_group', $id) : [],
            'canViewLogs' => $canViewLogs,
            'canUpdate' => $this->permissionGroupPolicy->canUpdate(),
            'canDelete' => $this->permissionGroupPolicy->canDelete(),
            ...$navigation->parameters(),
        ]);
    }
}

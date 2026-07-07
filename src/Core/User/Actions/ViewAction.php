<?php

declare(strict_types=1);

namespace App\Core\User\Actions;

use App\Core\Log\LogPolicy;
use App\Core\Log\LogReader;
use App\Core\Role\RoleRepository;
use App\Core\User\UserPolicy;
use App\Core\User\UserPresenter;
use App\Core\User\UserReader;
use App\Helpers\Translate;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ViewAction
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private UserReader $userReader,
        private RoleRepository $roleRepository,
        private UserPolicy $userPolicy,
        private LogReader $logReader,
        private LogPolicy $logPolicy,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/User/views');
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')]
        int $id,
    ): ResponseInterface {
        if (!$this->userPolicy->canView()) {
            return $this->webAction->forbidden();
        }

        $row = $this->userReader->getView($id);

        if ($row === null) {
            return $this->webAction->notFound(Translate::t('Utente non trovato.'));
        }

        $navigation = $this->webAction->viewNavigation('user', $id, $request, '/user');
        $canViewLogs = $this->logPolicy->canAccess();

        return $this->viewRenderer->render('view', [
            'user' => new UserPresenter($row),
            'roles' => $this->roleRepository->findByUserId($id),
            'logs' => $canViewLogs ? $this->logReader->findByEntity('user', $id) : [],
            'canViewLogs' => $canViewLogs,
            'canUpdate' => $this->userPolicy->canUpdate(),
            'canDelete' => $this->userPolicy->canDelete(),
            ...$navigation->parameters(),
        ]);
    }
}

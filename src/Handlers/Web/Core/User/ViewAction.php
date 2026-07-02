<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\User;

use App\Data\Core\Log\LogPolicy;
use App\Data\Core\Log\LogReader;
use App\Data\Core\Role\RoleRepository;
use App\Data\Core\User\UserPolicy;
use App\Data\Core\User\UserPresenter;
use App\Data\Core\User\UserReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ViewAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UserReader $userReader,
        private RoleRepository $roleRepository,
        private UserPolicy $userPolicy,
        private LogReader $logReader,
        private LogPolicy $logPolicy,
        private WebActionService $webAction,
    ) {}

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
            return $this->webAction->notFound('Utente non trovato.');
        }

        $navigation = $this->webAction->viewNavigation('user', $id, $request, '/user');
        $canViewLogs = $this->logPolicy->canAccess();

        return $this->viewRenderer->render('core/user/view', [
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

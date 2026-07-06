<?php

declare(strict_types=1);

namespace App\Mes\Task\Actions;

use App\Mes\Task\TaskPresenter;
use App\Mes\Task\TaskPolicy;
use App\Mes\Task\TaskReader;
use App\Data\Core\Log\LogPolicy;
use App\Data\Core\Log\LogReader;
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
        private TaskReader $taskReader,
        private TaskPolicy $taskPolicy,
        private LogReader $logReader,
        private LogPolicy $logPolicy,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Mes/Task/views');
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')]
        int $id,
    ): ResponseInterface {
        $row = $this->taskReader->getView($id);

        if (!$this->taskPolicy->canView() || $row === null) {
            return $this->webAction->forbidden();
        }

        $navigation = $this->webAction->viewNavigation('task', $id, $request, '/task');
        $canViewLogs = $this->logPolicy->canAccess();

        return $this->viewRenderer->render('view', [
            'task' => new TaskPresenter($row),
            'logs' => $canViewLogs ? $this->logReader->findByEntity('task', $id) : [],
            'canViewLogs' => $canViewLogs,
            'canUpdate' => $this->taskPolicy->canUpdate(),
            'canDelete' => $this->taskPolicy->canDelete(),
            ...$navigation->parameters(),
        ]);
    }
}

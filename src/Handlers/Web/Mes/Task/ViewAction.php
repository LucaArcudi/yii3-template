<?php

declare(strict_types=1);

namespace App\Handlers\Web\Mes\Task;

use App\Data\Mes\Task\TaskPresenter;
use App\Data\Mes\Task\TaskPolicy;
use App\Data\Mes\Task\TaskReader;
use App\Data\Core\Log\LogPolicy;
use App\Data\Core\Log\LogReader;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ViewAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private TaskReader $taskReader,
        private TaskPolicy $taskPolicy,
        private LogReader $logReader,
        private LogPolicy $logPolicy,
        private WebActionService $webAction,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')] int $id,
    ): ResponseInterface {
        $row = $this->taskReader->getView($id);

        if (!$this->taskPolicy->canView() || $row === null) {
            return $this->webAction->forbidden();
        }

        $navigation = $this->webAction->viewNavigation('task', $id, $request, '/task');
        $canViewLogs = $this->logPolicy->canAccess();

        return $this->viewRenderer->render('mes/task/view', [
            'task' => new TaskPresenter($row),
            'logs' => $canViewLogs ? $this->logReader->findByEntity('task', $id) : [],
            'canViewLogs' => $canViewLogs,
            'canUpdate' => $this->taskPolicy->canUpdate(),
            'canDelete' => $this->taskPolicy->canDelete(),
            ...$navigation->parameters(),
        ]);
    }
}

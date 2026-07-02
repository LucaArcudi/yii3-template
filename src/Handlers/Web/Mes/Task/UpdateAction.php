<?php

declare(strict_types=1);

namespace App\Handlers\Web\Mes\Task;

use App\Data\Mes\Task\TaskInput;
use App\Data\Mes\Task\TaskPolicy;
use App\Data\Mes\Task\TaskRepository;
use App\Helpers\Translate;
use App\Services\Core\CurrentActorProvider;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class UpdateAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private TaskRepository $taskRepository,
        private TaskPolicy $taskPolicy,
        private CurrentActorProvider $currentActorProvider,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {}

    public function __invoke(ServerRequestInterface $request, #[RouteArgument('id')] int $id, TaskInput $input): ResponseInterface
    {
        if (!$this->taskPolicy->canUpdate()) {
            return $this->webAction->forbidden();
        }

        $task = $this->taskRepository->findById($id);

        if ($task === null) {
            return $this->webAction->notFound();
        }

        $input->fill($task->toArray());

        if ($this->webAction->isPost($request)) {
            $input->fill($request->getParsedBody());
            $input->id = $id;
            $result = $input->validateUpdate();

            if ($result->isValid()) {
                $this->taskRepository->update($input->toTask($task, $this->currentActorProvider->id()));
                $this->flash->set('success', Translate::t('Task aggiornata con successo.'));

                return $this->webAction->redirectToView('task', $id);
            }

            return $this->viewRenderer->render('mes/task/form', [
                'mode' => 'update',
                'input' => $input,
                'errors' => $result->getErrorMessagesIndexedByProperty(),
                'validated' => true,
                'backUrl' => $this->webAction->updateBackUrl('task', $id, '/task'),
            ]);
        }

        $backUrl = $this->webAction->rememberUpdateBackUrl('task', $id, $request, '/task');

        return $this->viewRenderer->render('mes/task/form', [
            'mode' => 'update',
            'input' => $input,
            'errors' => [],
            'validated' => false,
            'backUrl' => $backUrl,
        ]);
    }
}

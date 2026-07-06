<?php

declare(strict_types=1);

namespace App\Mes\Task\Actions;

use App\Mes\Task\TaskInput;
use App\Mes\Task\TaskPolicy;
use App\Mes\Task\TaskRepository;
use App\Helpers\Translate;
use App\Services\Core\CurrentActorProvider;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class CreateAction
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private TaskRepository $taskRepository,
        private TaskPolicy $taskPolicy,
        private CurrentActorProvider $currentActorProvider,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Mes/Task/views');
    }

    public function __invoke(ServerRequestInterface $request, TaskInput $input): ResponseInterface
    {
        if (!$this->taskPolicy->canCreate()) {
            return $this->webAction->forbidden();
        }

        if ($this->webAction->isPost($request)) {
            $input->fill($request->getParsedBody());

            $result = $input->validateCreate();

            if ($result->isValid()) {
                $id = $this->taskRepository->create($input->toTask(actorId: $this->currentActorProvider->id()));
                $this->flash->set('success', Translate::t('Task creata con successo.'));

                return $this->webAction->redirectToView('task', $id);
            }

            return $this->viewRenderer->render('form', [
                'mode' => 'create',
                'input' => $input,
                'errors' => $result->getErrorMessagesIndexedByProperty(),
                'validated' => true,
                'backUrl' => $this->webAction->createBackUrl('task', '/task'),
            ]);
        }

        $backUrl = $this->webAction->rememberCreateBackUrl('task', $request, '/task');

        return $this->viewRenderer->render('form', [
            'mode' => 'create',
            'input' => $input,
            'errors' => [],
            'validated' => false,
            'backUrl' => $backUrl,
        ]);
    }
}

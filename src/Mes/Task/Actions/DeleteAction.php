<?php

declare(strict_types=1);

namespace App\Mes\Task\Actions;

use App\Mes\Task\TaskPolicy;
use App\Mes\Task\TaskRepository;
use App\Helpers\Translate;
use App\Services\Core\CurrentActorProvider;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\FlashInterface;

final readonly class DeleteAction
{
    public function __construct(
        private TaskRepository $taskRepository,
        private TaskPolicy $taskPolicy,
        private CurrentActorProvider $currentActorProvider,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {}

    public function __invoke(#[RouteArgument('id')] int $id): ResponseInterface
    {
        if (!$this->taskPolicy->canDelete()) {
            return $this->webAction->forbidden();
        }

        $redirectUrl = $this->webAction->viewBackUrl('task', $id, '/task');

        if (!$this->taskRepository->exists($id)) {
            return $this->webAction->notFound();
        }

        $this->taskRepository->delete($id, $this->currentActorProvider->id());
        $this->flash->set('success', Translate::t('Task eliminata con successo.'));

        return $this->webAction->redirect($redirectUrl);
    }
}

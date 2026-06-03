<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\Role;

use App\Data\Core\Role\RolePolicy;
use App\Data\Core\Role\RoleRepository;
use App\Services\Core\CurrentActorProvider;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\FlashInterface;

final readonly class DeleteAction
{
    public function __construct(
        private RoleRepository $roleRepository,
        private RolePolicy $rolePolicy,
        private CurrentActorProvider $currentActorProvider,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {
    }

    public function __invoke(#[RouteArgument('id')] int $id): ResponseInterface
    {
        if (!$this->rolePolicy->canDelete()) {
            return $this->webAction->forbidden();
        }

        $redirectUrl = $this->webAction->viewBackUrl('role', $id, '/role');

        if (!$this->roleRepository->exists($id)) {
            return $this->webAction->notFound();
        }

        if ($this->roleRepository->isAssignedToUsers($id)) {
            $this->flash->set('error', 'Il ruolo e assegnato a uno o piu utenti e non puo essere eliminato.');

            return $this->webAction->redirect($redirectUrl);
        }

        $this->roleRepository->delete($id, $this->currentActorProvider->id());
        $this->flash->set('success', 'Ruolo eliminato con successo.');

        return $this->webAction->redirect($redirectUrl);
    }
}

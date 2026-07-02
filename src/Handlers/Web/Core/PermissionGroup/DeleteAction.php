<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\PermissionGroup;

use App\Data\Core\Permission\PermissionGroupPolicy;
use App\Data\Core\Permission\PermissionGroupRepository;
use App\Services\Core\CurrentActorProvider;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\FlashInterface;

final readonly class DeleteAction
{
    public function __construct(
        private PermissionGroupRepository $permissionGroupRepository,
        private PermissionGroupPolicy $permissionGroupPolicy,
        private CurrentActorProvider $currentActorProvider,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {}

    public function __invoke(#[RouteArgument('id')] int $id): ResponseInterface
    {
        if (!$this->permissionGroupPolicy->canDelete()) {
            return $this->webAction->forbidden();
        }

        $redirectUrl = $this->webAction->viewBackUrl('permission-group', $id, '/permission-group');

        if (!$this->permissionGroupRepository->exists($id)) {
            return $this->webAction->notFound();
        }

        if ($this->permissionGroupRepository->isAssigned($id)) {
            $this->flash->set('error', 'Il gruppo e assegnato ad almeno un permesso e non puo essere eliminato.');

            return $this->webAction->redirect($redirectUrl);
        }

        $this->permissionGroupRepository->delete($id, $this->currentActorProvider->id());
        $this->flash->set('success', 'Gruppo permessi eliminato con successo.');

        return $this->webAction->redirect($redirectUrl);
    }
}

<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\Permission;

use App\Data\Core\Permission\PermissionPolicy;
use App\Data\Core\Permission\PermissionRepository;
use App\Services\Core\CurrentActorProvider;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\FlashInterface;

final readonly class DeleteAction
{
    public function __construct(
        private PermissionRepository $permissionRepository,
        private PermissionPolicy $permissionPolicy,
        private CurrentActorProvider $currentActorProvider,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {
    }

    public function __invoke(#[RouteArgument('id')] int $id): ResponseInterface
    {
        if (!$this->permissionPolicy->canDelete()) {
            return $this->webAction->forbidden();
        }

        $redirectUrl = $this->webAction->viewBackUrl('permission', $id, '/permission');

        if (!$this->permissionRepository->exists($id)) {
            return $this->webAction->notFound();
        }

        $this->permissionRepository->delete($id, $this->currentActorProvider->id());
        $this->flash->set('success', 'Permesso eliminato con successo.');

        return $this->webAction->redirect($redirectUrl);
    }
}

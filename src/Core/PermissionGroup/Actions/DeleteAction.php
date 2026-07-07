<?php

declare(strict_types=1);

namespace App\Core\PermissionGroup\Actions;

use App\Core\PermissionGroup\PermissionGroupPolicy;
use App\Core\PermissionGroup\PermissionGroupRepository;
use App\Shared\Helpers\Translate;
use App\Shared\Services\CurrentActorProvider;
use App\Shared\Services\WebActionService;
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
            $this->flash->set('error', Translate::t('Il gruppo e assegnato ad almeno un permesso e non puo essere eliminato.'));

            return $this->webAction->redirect($redirectUrl);
        }

        $this->permissionGroupRepository->delete($id, $this->currentActorProvider->id());
        $this->flash->set('success', Translate::t('Gruppo permessi eliminato con successo.'));

        return $this->webAction->redirect($redirectUrl);
    }
}

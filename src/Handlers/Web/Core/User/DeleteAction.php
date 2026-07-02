<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\User;

use App\Data\Core\User\UserPolicy;
use App\Data\Core\User\UserRepository;
use App\Helpers\Translate;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\User\CurrentUser;

final readonly class DeleteAction
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPolicy $userPolicy,
        private CurrentUser $currentUser,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {}

    public function __invoke(#[RouteArgument('id')] int $id): ResponseInterface
    {
        if (!$this->userPolicy->canDelete()) {
            return $this->webAction->forbidden();
        }

        $redirectUrl = $this->webAction->viewBackUrl('user', $id, '/user');

        if (!$this->userRepository->exists($id)) {
            return $this->webAction->notFound();
        }

        if ((int) ($this->currentUser->getId() ?? 0) === $id) {
            $this->flash->set('error', Translate::t('Non puoi eliminare l\'utente attualmente autenticato.'));

            return $this->webAction->redirect($redirectUrl);
        }

        $actorId = $this->currentUser->getId();
        $this->userRepository->delete($id, $actorId === null || $actorId === '' ? null : (int) $actorId);
        $this->flash->set('success', Translate::t('Utente eliminato con successo.'));

        return $this->webAction->redirect($redirectUrl);
    }
}

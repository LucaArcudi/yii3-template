<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\User;

use App\Data\Core\User\UserRepository;
use App\Services\Core\RememberMeCookieService;
use HttpSoft\Message\Response;
use HttpSoft\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\User\CurrentUser;

final readonly class DeleteProfileAction
{
    public function __construct(
        private UserRepository $userRepository,
        private CurrentUser $currentUser,
        private RememberMeCookieService $rememberMeCookie,
        private FlashInterface $flash,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = $this->currentUser->getId();

        if ($id === null || $id === '') {
            $this->flash->set('warning', 'Effettua il login per gestire il profilo.');

            return new RedirectResponse('/login');
        }

        $userId = (int) $id;

        if (!$this->userRepository->exists($userId)) {
            return new Response(404);
        }

        $this->userRepository->delete($userId, $userId);
        $this->currentUser->logout();
        $this->flash->set('info', 'Profilo eliminato correttamente.');

        return $this->rememberMeCookie->expireCookie(
            $request,
            (new Response(302))->withHeader('Location', '/login'),
        );
    }
}

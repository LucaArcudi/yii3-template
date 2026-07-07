<?php

declare(strict_types=1);

namespace App\Core\User\Actions;

use HttpSoft\Message\Response;
use App\Core\User\UserRepository;
use App\Helpers\Translate;
use App\Services\Core\RememberMeCookieService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\User\CurrentUser;

final readonly class LogoutAction
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserRepository $userRepository,
        private RememberMeCookieService $rememberMeCookie,
        private FlashInterface $flash,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = $this->currentUser->getId();

        if ($id !== null && $id !== '') {
            $this->userRepository->updateRememberToken((int) $id, null);
        }

        $this->currentUser->logout();
        $this->flash->set('info', Translate::t('Sessione terminata correttamente.'));

        return $this->rememberMeCookie->expireCookie(
            $request,
            (new Response(302))->withHeader('Location', '/login'),
        );
    }
}

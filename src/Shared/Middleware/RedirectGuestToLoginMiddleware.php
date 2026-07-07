<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Shared\Helpers\Translate;
use App\Shared\Services\RememberedUrlService;
use HttpSoft\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\User\CurrentUser;

final class RedirectGuestToLoginMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CurrentUser $currentUser,
        private FlashInterface $flash,
        private RememberedUrlService $rememberedUrl,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->currentUser->isGuest()) {
            return $handler->handle($request);
        }

        if ($request->getMethod() === 'GET') {
            $this->rememberedUrl->rememberCurrent('auth.return', $request);
        }

        $this->flash->set('warning', Translate::t('Effettua il login per continuare.'));

        return new RedirectResponse('login');

    }
}

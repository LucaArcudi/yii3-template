<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Core\User\UserRepository;
use App\Shared\Helpers\Translate;
use App\Shared\Services\RememberedUrlService;
use HttpSoft\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\User\CurrentUser;

use function in_array;

final readonly class PasswordExpiredMiddleware implements MiddlewareInterface
{
    private const EXCLUDED_PATHS = [
        '/change-password',
        '/forgot-email',
        '/forgot-password',
        '/login',
        '/logout',
        '/register',
    ];

    public function __construct(
        private CurrentUser $currentUser,
        private UserRepository $userRepository,
        private FlashInterface $flash,
        private RememberedUrlService $rememberedUrl,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->currentUser->isGuest()) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath() ?: '/';

        if (in_array($path, self::EXCLUDED_PATHS, true)) {
            return $handler->handle($request);
        }

        $userId = $this->currentUser->getId();
        $user = $userId === null || $userId === '' ? null : $this->userRepository->findById((int) $userId);

        if ($user === null || !$user->isPasswordExpired()) {
            return $handler->handle($request);
        }

        if ($request->getMethod() === 'GET') {
            $this->rememberedUrl->rememberCurrent('auth.password_return', $request);
        }

        $this->flash->set('warning', Translate::t('La password è scaduta: impostane una nuova per continuare.'));

        return new RedirectResponse('/change-password?reason=expired');
    }
}

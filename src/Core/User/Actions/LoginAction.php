<?php

declare(strict_types=1);

namespace App\Core\User\Actions;

use App\Core\Notification\NotificationRepository;
use App\Core\User\LoginInput;
use App\Core\User\UserIdentity;
use App\Core\User\UserRepository;
use App\Helpers\Translate;
use App\Services\Core\AuthRateLimitResult;
use App\Services\Core\AuthRateLimiter;
use App\Services\Core\AuthTokenService;
use App\Services\Core\PasswordHasher;
use App\Services\Core\RememberMeCookieService;
use App\Services\Core\RememberedUrlService;
use HttpSoft\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Session\SessionInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;
use Throwable;

use function date;

final readonly class LoginAction
{
    /**
     * Hash Argon2id fittizio: la verifica viene eseguita anche quando l'email non
     * esiste, così il tempo di risposta non rivela quali utenti sono registrati.
     */
    private const DUMMY_PASSWORD_HASH = '$argon2id$v=19$m=65536,t=4,p=1$Q0hkQnJrZjBCRWN5MjNQbQ$9E0TwhLIB/ENFoFCQ2sPBdibL7Zva6A+VR8M+jCqiyw';

    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private UserRepository $userRepository,
        private PasswordHasher $passwordHasher,
        private CurrentUser $currentUser,
        private RememberMeCookieService $rememberMeCookie,
        private AuthTokenService $authTokenService,
        private FlashInterface $flash,
        private RememberedUrlService $rememberedUrl,
        private AuthRateLimiter $rateLimiter,
        private NotificationRepository $notificationRepository,
        private SessionInterface $session,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/User/views');
    }

    public function __invoke(ServerRequestInterface $request, LoginInput $input): ResponseInterface
    {
        $renderer = $this->viewRenderer->withLayout('@resources/layouts/guest');

        if (!$this->currentUser->isGuest()) {
            return (new Response(302))
                ->withHeader('Location', '/');
        }

        if ($request->getMethod() === Method::POST) {

            $input->fill((array) $request->getParsedBody());

            $rateLimit = $this->rateLimiter->consumeLogin($request, $input->email);

            if (!$rateLimit->allowed) {
                return $this->renderRateLimited($renderer, $input, $rateLimit);
            }

            $result = $input->validateLogin();

            $errors = $result->getErrorMessagesIndexedByProperty();

            if ($errors === []) {
                $user = $this->userRepository->findByEmail((string) $input->email);

                $passwordValid = $this->passwordHasher->verify(
                    (string) $input->password,
                    $user->passwordHash ?? self::DUMMY_PASSWORD_HASH,
                );

                if (
                    $user === null ||
                    !$user->isActive() ||
                    !$passwordValid
                ) {
                    $errors['password'][] = Translate::t('Credenziali non valide.');
                } else {
                    $rememberToken = null;
                    $rememberTokenHash = $user->rememberTokenHash;

                    if ($input->rememberMe) {
                        $rememberToken = $this->authTokenService->generateRememberToken();
                        $rememberTokenHash = $this->authTokenService->hash($rememberToken);
                        $this->userRepository->updateRememberToken((int) $user->id, $rememberTokenHash);
                    }

                    $identity = new UserIdentity(
                        (string) $user->id,
                        $user->email,
                        $user->name,
                        $rememberTokenHash,
                        $rememberToken,
                    );

                    if ($this->currentUser->login($identity)) {
                        // Previene la session fixation: nuovo ID al cambio di privilegio.
                        $this->session->regenerateId();
                        $this->rateLimiter->clearLoginIdentity($user->email);
                        $this->userRepository->updateLastLogin((int) $user->id, date('Y-m-d H:i:s'));
                        $this->notifyLogin((int) $user->id);

                        $returnUrl = $this->rememberedUrl->pull('auth.return', '/');
                        $location = $returnUrl;

                        if ($user->isPasswordExpired()) {
                            $this->rememberedUrl->remember('auth.password_return', $returnUrl);
                            $location = '/change-password?reason=expired';
                            $this->flash->set('warning', Translate::t('La password è scaduta: impostane una nuova per continuare.'));
                        } else {
                            $this->flash->set('success', Translate::t('Bentornato, {name}.', ['name' => $user->name]));
                        }

                        $response = (new Response(302))->withHeader('Location', $location);

                        return $input->rememberMe
                            ? $this->rememberMeCookie->addCookie($identity, $request, $response)
                            : $this->rememberMeCookie->expireCookie($request, $response);
                    }

                    $errors['email'][] = Translate::t('Impossibile avviare la sessione.');
                }
            }

            return $renderer->render('login', [
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
            ]);
        }

        return $renderer->render('login', [
            'input' => $input,
            'errors' => [],
            'validated' => false,
        ]);
    }

    private function notifyLogin(int $userId): void
    {
        try {
            $this->notificationRepository->notifyUser(
                userId: $userId,
                title: Translate::t('Accesso effettuato'),
                description: Translate::t('Hai effettuato un nuovo accesso alla piattaforma.'),
                url: '/profile',
                actorId: $userId,
            );
        } catch (Throwable) {
        }
    }

    private function renderRateLimited(
        WebViewRenderer $renderer,
        LoginInput $input,
        AuthRateLimitResult $rateLimit,
    ): ResponseInterface {
        return $renderer->render('login', [
            'input' => $input,
            'errors' => ['' => [$rateLimit->message()]],
            'validated' => true,
        ])
            ->withStatus(Status::TOO_MANY_REQUESTS)
            ->withHeader('Retry-After', (string) $rateLimit->retryAfterSeconds);
    }
}

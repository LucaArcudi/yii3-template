<?php

declare(strict_types=1);

namespace App\Core\User\Actions;

use App\Core\User\ChangePasswordInput;
use App\Core\User\UserEntity;
use App\Core\User\UserIdentity;
use App\Core\User\UserRepository;
use App\Shared\Helpers\Translate;
use App\Shared\Params\AuthParams;
use App\Shared\Services\AuthRateLimitResult;
use App\Shared\Services\AuthRateLimiter;
use App\Shared\Services\AuthTokenService;
use App\Shared\Services\PasswordHasher;
use App\Shared\Services\RememberMeCookieService;
use App\Shared\Services\RememberedUrlService;
use HttpSoft\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

use function date;
use function hash;
use function is_array;
use function is_string;
use function trim;

final readonly class ChangePasswordAction
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private UserRepository $userRepository,
        private PasswordHasher $passwordHasher,
        private AuthTokenService $authTokenService,
        private AuthParams $authParams,
        private CurrentUser $currentUser,
        private RememberMeCookieService $rememberMeCookie,
        private FlashInterface $flash,
        private RememberedUrlService $rememberedUrl,
        private AuthRateLimiter $rateLimiter,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/User/views');
    }

    public function __invoke(ServerRequestInterface $request, ChangePasswordInput $input): ResponseInterface
    {
        $token = $this->tokenFromRequest($request);
        $tokenMode = $token !== null;
        $tokenUser = null;

        if ($tokenMode) {
            $input->token = $token;
            $tokenUser = $this->findUserByResetToken($token);

            if ($tokenUser === null && $request->getMethod() !== Method::POST) {
                $this->flash->set('error', Translate::t('Il link per cambiare la password non e valido o e scaduto.'));

                return new RedirectResponse('/forgot-password');
            }
        }

        if (!$tokenMode && $this->currentUser->isGuest()) {
            $this->rememberedUrl->rememberCurrent('auth.return', $request);
            $this->flash->set('warning', Translate::t('Effettua il login per cambiare la password.'));

            return new RedirectResponse('/login');
        }

        $user = $tokenMode ? $tokenUser : $this->currentUserEntity();

        if (!$tokenMode && $user === null) {
            $this->flash->set('error', Translate::t('Utente non trovato.'));

            return new RedirectResponse('/login');
        }

        $requiresCurrentPassword = !$tokenMode;
        $renderer = $tokenMode && $this->currentUser->isGuest()
            ? $this->viewRenderer->withLayout('@resources/layouts/guest')
            : $this->viewRenderer;

        if ($request->getMethod() === Method::POST) {
            $input->fill((array) $request->getParsedBody());

            $rateLimit = $this->rateLimiter->consumePasswordChange(
                $request,
                $this->rateLimitIdentity($tokenMode, $input->token, $user),
            );

            if (!$rateLimit->allowed) {
                return $this->renderRateLimited(
                    $renderer,
                    $input,
                    $rateLimit,
                    $requiresCurrentPassword,
                    $tokenMode,
                    $request,
                );
            }

            $result = $input->validateChangePassword($requiresCurrentPassword);
            $errors = $result->getErrorMessagesIndexedByProperty();

            if ($tokenMode) {
                $user = $this->findUserByResetToken((string) $input->token);

                if ($user === null) {
                    $errors[''][] = Translate::t('Il link per cambiare la password non e valido o e scaduto.');
                }
            } elseif ($user !== null && !$this->passwordHasher->verify((string) $input->currentPassword, $user->passwordHash)) {
                $errors['currentPassword'][] = Translate::t('La password attuale non e corretta.');
            }

            if (($errors['password'] ?? []) === []
                && $user !== null
                && $this->passwordHasher->verify((string) $input->password, $user->passwordHash)
            ) {
                $errors['password'][] = Translate::t('La nuova password deve essere diversa da quella attuale.');
            }

            if ($errors === []) {
                $changedAt = date('Y-m-d H:i:s');

                $this->userRepository->changePassword(
                    (int) $user->id,
                    $this->passwordHasher->hash((string) $input->password),
                    $changedAt,
                    $this->authParams->passwordExpiresAt($changedAt),
                    (int) $user->id,
                );

                $this->currentUser->login(new UserIdentity(
                    (string) $user->id,
                    $user->email,
                    $user->name,
                ));
                $this->rateLimiter->clearPasswordChangeIdentity('user:' . $user->id);

                $this->flash->set('success', Translate::t('Password aggiornata correttamente.'));

                $response = new RedirectResponse($this->rememberedUrl->pull('auth.password_return', '/'));

                return $this->rememberMeCookie->expireCookie($request, $response);
            }

            return $renderer->render('change-password', [
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
                'requiresCurrentPassword' => $requiresCurrentPassword,
                'tokenMode' => $tokenMode,
                'reason' => $this->reasonFromRequest($request),
            ]);
        }

        return $renderer->render('change-password', [
            'input' => $input,
            'errors' => [],
            'validated' => false,
            'requiresCurrentPassword' => $requiresCurrentPassword,
            'tokenMode' => $tokenMode,
            'reason' => $this->reasonFromRequest($request),
        ]);
    }

    private function currentUserEntity(): ?UserEntity
    {
        $id = $this->currentUser->getId();

        return $id === null || $id === '' ? null : $this->userRepository->findById((int) $id);
    }

    private function findUserByResetToken(string $token): ?UserEntity
    {
        $parts = $this->authTokenService->splitResetToken($token);

        if ($parts === null) {
            return null;
        }

        $user = $this->userRepository->findByPasswordResetSelector($parts['selector']);

        if ($user === null || !$user->isActive() || $user->isPasswordResetTokenExpired()) {
            return null;
        }

        return $this->authTokenService->verify($parts['verifier'], $user->passwordResetTokenHash)
            ? $user
            : null;
    }

    private function tokenFromRequest(ServerRequestInterface $request): ?string
    {
        $body = $request->getParsedBody();

        if (is_array($body) && isset($body['token']) && is_string($body['token']) && trim($body['token']) !== '') {
            return trim($body['token']);
        }

        $query = $request->getQueryParams();
        $token = $query['token'] ?? null;

        return is_string($token) && trim($token) !== '' ? trim($token) : null;
    }

    private function reasonFromRequest(ServerRequestInterface $request): ?string
    {
        $reason = $request->getQueryParams()['reason'] ?? null;

        return is_string($reason) && $reason !== '' ? $reason : null;
    }

    private function rateLimitIdentity(bool $tokenMode, ?string $token, ?UserEntity $user): ?string
    {
        if ($user !== null) {
            return 'user:' . $user->id;
        }

        return $tokenMode && $token !== null && $token !== ''
            ? 'token:' . hash('sha256', $token)
            : null;
    }

    private function renderRateLimited(
        WebViewRenderer $renderer,
        ChangePasswordInput $input,
        AuthRateLimitResult $rateLimit,
        bool $requiresCurrentPassword,
        bool $tokenMode,
        ServerRequestInterface $request,
    ): ResponseInterface {
        return $renderer->render('change-password', [
            'input' => $input,
            'errors' => ['' => [$rateLimit->message()]],
            'validated' => true,
            'requiresCurrentPassword' => $requiresCurrentPassword,
            'tokenMode' => $tokenMode,
            'reason' => $this->reasonFromRequest($request),
        ])
            ->withStatus(Status::TOO_MANY_REQUESTS)
            ->withHeader('Retry-After', (string) $rateLimit->retryAfterSeconds);
    }
}

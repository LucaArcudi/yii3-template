<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\User;

use App\Data\Core\User\ForgotPasswordInput;
use App\Data\Core\User\UserRepository;
use App\Params\Core\AuthParams;
use App\Services\Core\AuthRateLimitResult;
use App\Services\Core\AuthRateLimiter;
use App\Services\Core\AuthTokenService;
use App\Services\Core\Mail\Mailer;
use HttpSoft\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

use function http_build_query;
use function sprintf;

final readonly class ForgotPasswordAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UserRepository $userRepository,
        private AuthTokenService $authTokenService,
        private AuthParams $authParams,
        private Mailer $mailer,
        private LoggerInterface $logger,
        private FlashInterface $flash,
        private CurrentUser $currentUser,
        private AuthRateLimiter $rateLimiter,
    ) {}

    public function __invoke(ServerRequestInterface $request, ForgotPasswordInput $input): ResponseInterface
    {
        if (!$this->currentUser->isGuest()) {
            return new RedirectResponse('/change-password');
        }

        $renderer = $this->viewRenderer->withLayout('@resources/layouts/guest');

        if ($request->getMethod() === Method::POST) {
            $input->fill((array) $request->getParsedBody());

            $rateLimit = $this->rateLimiter->consumePasswordReset($request, $input->email);

            if (!$rateLimit->allowed) {
                return $this->renderRateLimited($renderer, $input, $rateLimit);
            }

            $result = $input->validateForgotPassword();
            $errors = $result->getErrorMessagesIndexedByProperty();

            if ($errors === []) {
                $user = $this->userRepository->findByEmail((string) $input->email);

                if ($user !== null && $user->isActive()) {
                    $token = $this->authTokenService->generateResetToken();
                    $expiresAt = $this->authParams->passwordResetTokenExpiresAt();

                    $this->userRepository->storePasswordResetToken(
                        (int) $user->id,
                        $token['selector'],
                        $this->authTokenService->hash($token['verifier']),
                        $expiresAt,
                    );

                    $this->sendResetEmail($request, $user->email, $user->name, $token['token']);
                }

                $this->flash->set(
                    'info',
                    'Se l\'account esiste ed è attivo, riceverai un link per cambiare la password.',
                );

                return new RedirectResponse('/login');
            }

            return $renderer->render('core/user/forgot-password', [
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
            ]);
        }

        return $renderer->render('core/user/forgot-password', [
            'input' => $input,
            'errors' => [],
            'validated' => false,
        ]);
    }

    private function renderRateLimited(
        WebViewRenderer $renderer,
        ForgotPasswordInput $input,
        AuthRateLimitResult $rateLimit,
    ): ResponseInterface {
        return $renderer->render('core/user/forgot-password', [
            'input' => $input,
            'errors' => ['' => [$rateLimit->message()]],
            'validated' => true,
        ])
            ->withStatus(Status::TOO_MANY_REQUESTS)
            ->withHeader('Retry-After', (string) $rateLimit->retryAfterSeconds);
    }

    private function sendResetEmail(
        ServerRequestInterface $request,
        string $email,
        string $name,
        string $token,
    ): void {
        $resetUrl = (string) $request
            ->getUri()
            ->withPath('/change-password')
            ->withQuery(http_build_query(['token' => $token]))
            ->withFragment('');

        try {
            $this->mailer->sendView(
                toEmail: $email,
                toName: $name,
                subject: 'Cambio password',
                view: 'core/user/password-reset',
                parameters: [
                    'name' => $name,
                    'resetUrl' => $resetUrl,
                    'expiresMinutes' => $this->authParams->passwordResetTokenTtlMinutes,
                    'preheader' => 'Usa il link per impostare una nuova password.',
                ],
                textBody: sprintf(
                    "Ciao %s,\n\nUsa questo link per impostare una nuova password: %s\n\nIl link scade tra %d minuti.\n",
                    $name,
                    $resetUrl,
                    $this->authParams->passwordResetTokenTtlMinutes,
                ),
            );
        } catch (Throwable $exception) {
            $this->logger->warning('Unable to send password reset email.', [
                'email' => $email,
                'exception' => $exception,
            ]);
        }
    }
}

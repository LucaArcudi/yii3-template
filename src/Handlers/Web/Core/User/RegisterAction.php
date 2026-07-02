<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\User;

use App\Data\Core\Role\RoleRepository;
use App\Data\Core\User\RegisterInput;
use App\Data\Core\User\UserEntity;
use App\Data\Core\User\UserRepository;
use App\Params\Core\AuthParams;
use App\Services\Core\AuthRateLimitResult;
use App\Services\Core\AuthRateLimiter;
use App\Services\Core\Mail\Mailer;
use App\Services\Core\MathCaptchaService;
use App\Services\Core\PasswordHasher;
use HttpSoft\Message\Response;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

use function date;
use function trim;

final readonly class RegisterAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private PasswordHasher $passwordHasher,
        private AuthParams $authParams,
        private Mailer $mailer,
        private LoggerInterface $logger,
        private FlashInterface $flash,
        private MathCaptchaService $captcha,
        private AuthRateLimiter $rateLimiter,
    ) {}

    public function __invoke(ServerRequestInterface $request, RegisterInput $input): ResponseInterface
    {
        $renderer = $this->viewRenderer->withLayout('@resources/layouts/guest');

        if ($request->getMethod() === Method::POST) {
            $input->fill((array) $request->getParsedBody());

            $rateLimit = $this->rateLimiter->consumeRegistration($request, $input->email);

            if (!$rateLimit->allowed) {
                return $this->renderRateLimited($renderer, $input, $rateLimit);
            }

            $result = $input->validateRegister();
            $errors = $result->getErrorMessagesIndexedByProperty();

            if (!$this->captcha->validate($input->captcha)) {
                $errors['captcha'][] = 'Risposta di verifica non corretta o scaduta.';
            }

            if ($input->website !== null && $input->website !== '') {
                $errors[''][] = 'Registrazione non completata. Riprova tra qualche minuto.';
            }

            if (($errors['email'] ?? []) === []
                && $input->email !== null
                && $this->userRepository->findByEmail($input->email) !== null
            ) {
                $errors['email'][] = 'Esiste gia un account con questa email.';
            }

            if ($errors === []) {
                $roleIds = $this->defaultRegistrationRoleIds($errors);
            }

            if ($errors === []) {
                $passwordChangedAt = date('Y-m-d H:i:s');

                $user = new UserEntity(
                    id: null,
                    email: $input->email,
                    passwordHash: $this->passwordHasher->hash((string) $input->password),
                    name: $input->name,
                    status: UserEntity::STATUS_ACTIVE,
                    passwordChangedAt: $passwordChangedAt,
                    passwordExpiresAt: $this->authParams->passwordExpiresAt($passwordChangedAt),
                );

                if ($roleIds === []) {
                    $this->userRepository->create($user);
                } else {
                    $this->userRepository->createWithRoles($user, $roleIds);
                }

                $this->flash->set('success', 'Account creato correttamente. Ora puoi accedere.');
                $this->sendWelcomeEmail($request, (string) $input->email, (string) $input->name);

                return (new Response(302))
                    ->withHeader('Location', '/login');
            }

            return $renderer->render('core/user/register', [
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
                'captcha' => $this->captcha->generate(),
            ]);
        }

        return $renderer->render('core/user/register', [
            'input' => $input,
            'errors' => [],
            'validated' => false,
            'captcha' => $this->captcha->generate(),
        ]);
    }

    private function renderRateLimited(
        WebViewRenderer $renderer,
        RegisterInput $input,
        AuthRateLimitResult $rateLimit,
    ): ResponseInterface {
        return $renderer->render('core/user/register', [
            'input' => $input,
            'errors' => ['' => [$rateLimit->message()]],
            'validated' => true,
            'captcha' => $this->captcha->generate(),
        ])
            ->withStatus(Status::TOO_MANY_REQUESTS)
            ->withHeader('Retry-After', (string) $rateLimit->retryAfterSeconds);
    }

    /**
     * @param array<string, list<string>> $errors
     *
     * @return int[]
     */
    private function defaultRegistrationRoleIds(array &$errors): array
    {
        $roleCode = trim($this->authParams->defaultRegistrationRoleCode);

        if ($roleCode === '') {
            return [];
        }

        $roleId = $this->roleRepository->findIdByCode($roleCode);

        if ($roleId === null) {
            $errors[''][] = sprintf('Ruolo predefinito di registrazione "%s" non configurato.', $roleCode);

            return [];
        }

        return [$roleId];
    }

    private function sendWelcomeEmail(ServerRequestInterface $request, string $email, string $name): void
    {
        $loginUrl = (string) $request
            ->getUri()
            ->withPath('/login')
            ->withQuery('')
            ->withFragment('');

        try {
            $this->mailer->sendView(
                toEmail: $email,
                toName: $name,
                subject: 'Account creato',
                view: 'core/user/welcome',
                parameters: [
                    'name' => $name,
                    'loginUrl' => $loginUrl,
                    'preheader' => 'Il tuo account è pronto per il login.',
                ],
                textBody: sprintf(
                    "Ciao %s,\n\nIl tuo account è stato creato correttamente.\nAccedi da: %s\n",
                    $name,
                    $loginUrl,
                ),
            );
        } catch (Throwable $exception) {
            $this->logger->warning('Unable to send welcome email after registration.', [
                'email' => $email,
                'exception' => $exception,
            ]);

            $this->flash->set('warning', 'Account creato, ma non è stato possibile inviare l\'email di conferma.');
        }
    }
}

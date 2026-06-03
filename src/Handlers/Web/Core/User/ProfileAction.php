<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\User;

use App\Data\Core\User\UserIdentity;
use App\Data\Core\User\UserInput;
use App\Data\Core\User\UserEntity;
use App\Data\Core\User\UserRepository;
use App\Services\Core\PasswordHasher;
use HttpSoft\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Method;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class ProfileAction
{
    private const FORM_PROFILE = 'profile';
    private const FORM_EMAIL = 'email';

    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UserRepository $userRepository,
        private PasswordHasher $passwordHasher,
        private CurrentUser $currentUser,
        private FlashInterface $flash,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, UserInput $input): ResponseInterface
    {
        $user = $this->currentUserEntity();

        if ($user === null) {
            $this->flash->set('warning', 'Effettua il login per gestire il profilo.');

            return new RedirectResponse('/login');
        }

        $input->fill($user->toArray());

        if ($request->getMethod() === Method::POST) {
            $body = (array) $request->getParsedBody();
            $form = (string) ($body['form'] ?? self::FORM_PROFILE);

            return match ($form) {
                self::FORM_EMAIL => $this->handleEmailChange($user, $input, $body),
                self::FORM_PROFILE => $this->handleProfileUpdate($user, $input, $body),
                default => $this->renderProfile(
                    $user,
                    $input,
                    profileErrors: ['' => ['Richiesta non valida.']],
                    profileValidated: true,
                ),
            };
        }

        return $this->renderProfile($user, $input);
    }

    private function handleProfileUpdate(UserEntity $user, UserInput $input, array $body): ResponseInterface
    {
        $input->fill($body);
        $input->id = (int) $user->id;
        $input->email = $user->email;
        $input->status = $user->status;

        $result = $input->validateProfile();
        $errors = $result->getErrorMessagesIndexedByProperty();

        if ($errors !== []) {
            return $this->renderProfile($user, $input, profileErrors: $errors, profileValidated: true);
        }

        $updatedUser = $input->toUser(
            passwordHash: $user->passwordHash,
            existingUser: $user,
            actorId: (int) $user->id,
        );
        $updatedUser->email = $user->email;
        $updatedUser->status = $user->status;

        $this->userRepository->updateProfile($updatedUser);
        $this->refreshIdentity($updatedUser);
        $this->flash->set('success', 'Profilo aggiornato correttamente.');

        return new RedirectResponse('/profile');
    }

    private function handleEmailChange(UserEntity $user, UserInput $input, array $body): ResponseInterface
    {
        $input->fill($body);
        $input->id = (int) $user->id;
        $input->name = $user->name;
        $input->status = $user->status;

        $result = $input->validateEmailChange();
        $errors = $result->getErrorMessagesIndexedByProperty();

        if (($errors['email'] ?? []) === []
            && $input->email !== null
            && $input->email !== ''
            && $input->email !== $user->email
            && $this->userRepository->emailExists($input->email, (int) $user->id)
        ) {
            $errors['email'][] = 'Esiste gia un utente con questa email.';
        }

        if (($errors['currentPassword'] ?? []) === []
            && !$this->passwordHasher->verify((string) $input->currentPassword, $user->passwordHash)
        ) {
            $errors['currentPassword'][] = 'Password attuale non corretta.';
        }

        if ($errors !== []) {
            return $this->renderProfile($user, $input, emailErrors: $errors, emailValidated: true);
        }

        if ($input->email === $user->email) {
            $this->flash->set('info', 'Questa email e gia associata al tuo profilo.');

            return new RedirectResponse('/profile');
        }

        $updatedUser = $input->toUser(
            passwordHash: $user->passwordHash,
            existingUser: $user,
            actorId: (int) $user->id,
        );
        $updatedUser->name = $user->name;
        $updatedUser->status = $user->status;

        $this->userRepository->updateProfile($updatedUser);
        $this->refreshIdentity($updatedUser);
        $this->flash->set('success', 'Email aggiornata correttamente.');

        return new RedirectResponse('/profile');
    }

    private function renderProfile(
        UserEntity $user,
        UserInput $input,
        array $profileErrors = [],
        bool $profileValidated = false,
        array $emailErrors = [],
        bool $emailValidated = false,
    ): ResponseInterface {
        return $this->viewRenderer->render('core/user/profile', [
            'input' => $input,
            'profileErrors' => $profileErrors,
            'profileValidated' => $profileValidated,
            'emailErrors' => $emailErrors,
            'emailValidated' => $emailValidated,
            'userId' => (int) $user->id,
            'currentEmail' => $user->email,
        ]);
    }

    private function refreshIdentity(UserEntity $user): void
    {
        $this->currentUser->login(new UserIdentity(
            (string) $user->id,
            $user->email,
            $user->name,
        ));
    }

    private function currentUserEntity(): ?UserEntity
    {
        $id = $this->currentUser->getId();

        return $id === null || $id === '' ? null : $this->userRepository->findById((int) $id);
    }
}

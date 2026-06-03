<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\User;

use App\Data\Core\Role\RoleRepository;
use App\Data\Core\User\UserInput;
use App\Data\Core\User\UserPolicy;
use App\Data\Core\User\UserRepository;
use App\Params\Core\AuthParams;
use App\Services\Core\CurrentActorProvider;
use App\Services\Core\PasswordHasher;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

use function date;

final readonly class UpdateAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private UserPolicy $userPolicy,
        private CurrentActorProvider $currentActorProvider,
        private PasswordHasher $passwordHasher,
        private AuthParams $authParams,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')] int $id,
        UserInput $input,
    ): ResponseInterface {
        if (!$this->userPolicy->canUpdate()) {
            return $this->webAction->forbidden();
        }

        $user = $this->userRepository->findById($id);

        if ($user === null) {
            return $this->webAction->notFound();
        }

        $roleOptions = $this->roleRepository->findSelectableOptions();
        $input->fill($user->toArray());
        $input->setRoleIds($this->userRepository->getRoleIds($id));

        if ($this->webAction->isPost($request)) {
            $input->fill($request->getParsedBody());
            $input->id = $id;

            $result = $input->validateUpdate();
            $errors = $result->getErrorMessagesIndexedByProperty();
            $errors = $this->validateRoleSelection($input, $errors);

            if (($errors['email'] ?? []) === []
                && $input->email !== null
                && $input->email !== ''
                && $this->userRepository->emailExists($input->email, $id)
            ) {
                $errors['email'][] = 'Esiste gia un utente con questa email.';
            }

            if ($errors === []) {
                $updatePassword = $input->password !== null && $input->password !== '';
                $passwordHash = $updatePassword
                    ? $this->passwordHasher->hash((string) $input->password)
                    : $user->passwordHash;

                $updatedUser = $input->toUser($passwordHash, $user, $this->currentActorProvider->id());

                if ($updatePassword) {
                    $passwordChangedAt = date('Y-m-d H:i:s');
                    $updatedUser->rememberTokenHash = null;
                    $updatedUser->passwordChangedAt = $passwordChangedAt;
                    $updatedUser->passwordExpiresAt = $this->authParams->passwordExpiresAt($passwordChangedAt);
                    $updatedUser->passwordResetSelector = null;
                    $updatedUser->passwordResetTokenHash = null;
                    $updatedUser->passwordResetTokenExpiresAt = null;
                }

                $this->userRepository->updateWithRoles($updatedUser, $input->roleIds, $updatePassword);
                $this->flash->set('success', 'Utente aggiornato con successo.');

                return $this->webAction->redirectToView('user', $id);
            }

            return $this->viewRenderer->render('core/user/form', [
                'mode' => 'update',
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
                'backUrl' => $this->webAction->updateBackUrl('user', $id, '/user'),
                'roleOptions' => $roleOptions,
            ]);
        }

        $backUrl = $this->webAction->rememberUpdateBackUrl('user', $id, $request, '/user');

        return $this->viewRenderer->render('core/user/form', [
            'mode' => 'update',
            'input' => $input,
            'errors' => [],
            'validated' => false,
            'backUrl' => $backUrl,
            'roleOptions' => $roleOptions,
        ]);
    }

    private function validateRoleSelection(UserInput $input, array $errors): array
    {
        if ($input->hasInvalidRoleSelection()) {
            $errors['roleIds'][] = 'La selezione dei ruoli non e valida.';
        }

        $existingRoleIds = $this->roleRepository->findExistingIds($input->roleIds);

        if (count($existingRoleIds) !== count($input->roleIds)) {
            $errors['roleIds'][] = 'Uno o piu ruoli selezionati non esistono piu.';
        }

        return $errors;
    }
}

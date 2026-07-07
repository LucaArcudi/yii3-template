<?php

declare(strict_types=1);

namespace App\Core\User\Actions;

use App\Core\Role\RoleRepository;
use App\Core\User\UserInput;
use App\Core\User\UserPolicy;
use App\Core\User\UserRepository;
use App\Helpers\Translate;
use App\Params\Core\AuthParams;
use App\Services\Core\CurrentActorProvider;
use App\Services\Core\PasswordHasher;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

use function date;

final readonly class CreateAction
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private UserPolicy $userPolicy,
        private CurrentActorProvider $currentActorProvider,
        private PasswordHasher $passwordHasher,
        private AuthParams $authParams,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/User/views');
    }

    public function __invoke(ServerRequestInterface $request, UserInput $input): ResponseInterface
    {
        if (!$this->userPolicy->canCreate()) {
            return $this->webAction->forbidden();
        }

        $roleOptions = $this->roleRepository->findSelectableOptions();

        if ($this->webAction->isPost($request)) {
            $input->fill($request->getParsedBody());

            $result = $input->validateCreate();
            $errors = $result->getErrorMessagesIndexedByProperty();
            $errors = $this->validateRoleSelection($input, $errors);

            if (($errors['email'] ?? []) === []
                && $input->email !== null
                && $input->email !== ''
                && $this->userRepository->emailExists($input->email)
            ) {
                $errors['email'][] = Translate::t('Esiste gia un utente con questa email.');
            }

            if ($errors === []) {
                $passwordChangedAt = date('Y-m-d H:i:s');
                $user = $input->toUser(
                    $this->passwordHasher->hash((string) $input->password),
                    actorId: $this->currentActorProvider->id(),
                );
                $user->passwordChangedAt = $passwordChangedAt;
                $user->passwordExpiresAt = $this->authParams->passwordExpiresAt($passwordChangedAt);

                $id = $this->userRepository->createWithRoles($user, $input->roleIds);
                $this->flash->set('success', Translate::t('Utente creato con successo.'));

                return $this->webAction->redirectToView('user', $id);
            }

            return $this->viewRenderer->render('form', [
                'mode' => 'create',
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
                'backUrl' => $this->webAction->createBackUrl('user', '/user'),
                'roleOptions' => $roleOptions,
            ]);
        }

        $backUrl = $this->webAction->rememberCreateBackUrl('user', $request, '/user');

        return $this->viewRenderer->render('form', [
            'mode' => 'create',
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
            $errors['roleIds'][] = Translate::t('La selezione dei ruoli non e valida.');
        }

        $existingRoleIds = $this->roleRepository->findExistingIds($input->roleIds);

        if (count($existingRoleIds) !== count($input->roleIds)) {
            $errors['roleIds'][] = Translate::t('Uno o piu ruoli selezionati non esistono piu.');
        }

        return $errors;
    }
}

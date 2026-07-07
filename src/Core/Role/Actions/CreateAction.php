<?php

declare(strict_types=1);

namespace App\Core\Role\Actions;

use App\Core\Permission\PermissionRepository;
use App\Core\Role\RoleInput;
use App\Core\Role\RolePolicy;
use App\Core\Role\RoleRepository;
use App\Shared\Helpers\Translate;
use App\Shared\Services\CurrentActorProvider;
use App\Shared\Services\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class CreateAction
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private RoleRepository $roleRepository,
        private PermissionRepository $permissionRepository,
        private RolePolicy $rolePolicy,
        private CurrentActorProvider $currentActorProvider,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/Role/views');
    }

    public function __invoke(ServerRequestInterface $request, RoleInput $input): ResponseInterface
    {
        if (!$this->rolePolicy->canCreate()) {
            return $this->webAction->forbidden();
        }

        $permissionGroups = $this->permissionRepository->findGroupedForRoleAssignment();

        if ($this->webAction->isPost($request)) {
            $input->fill($request->getParsedBody());

            $result = $input->validateCreate();
            $errors = $result->getErrorMessagesIndexedByProperty();
            $errors = $this->validatePermissionSelection($input, $errors);

            if (($errors['code'] ?? []) === []
                && $input->code !== null
                && $input->code !== ''
                && $this->roleRepository->codeExists($input->code)
            ) {
                $errors['code'][] = Translate::t('Questo codice e gia assegnato a un altro ruolo.');
            }

            if (($errors['name'] ?? []) === []
                && $input->name !== null
                && $input->name !== ''
                && $this->roleRepository->nameExists($input->name)
            ) {
                $errors['name'][] = Translate::t('Questo nome e gia assegnato a un altro ruolo.');
            }

            if ($errors === []) {
                $id = $this->roleRepository->createWithPermissions(
                    $input->toRole(actorId: $this->currentActorProvider->id()),
                    $input->permissionIds,
                );
                $this->flash->set('success', Translate::t('Ruolo creato con successo.'));

                return $this->webAction->redirectToView('role', $id);
            }

            return $this->viewRenderer->render('form', [
                'mode' => 'create',
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
                'backUrl' => $this->webAction->createBackUrl('role', '/role'),
                'permissionGroups' => $permissionGroups,
            ]);
        }

        $backUrl = $this->webAction->rememberCreateBackUrl('role', $request, '/role');

        return $this->viewRenderer->render('form', [
            'mode' => 'create',
            'input' => $input,
            'errors' => [],
            'validated' => false,
            'backUrl' => $backUrl,
            'permissionGroups' => $permissionGroups,
        ]);
    }

    private function validatePermissionSelection(RoleInput $input, array $errors): array
    {
        if ($input->hasInvalidPermissionSelection()) {
            $errors['permissionIds'][] = Translate::t('La selezione dei permessi non e valida.');
        }

        $existingPermissionIds = $this->permissionRepository->findExistingIds($input->permissionIds);

        if (count($existingPermissionIds) !== count($input->permissionIds)) {
            $errors['permissionIds'][] = Translate::t('Uno o piu permessi selezionati non esistono piu.');
        }

        return $errors;
    }
}

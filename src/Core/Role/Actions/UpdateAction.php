<?php

declare(strict_types=1);

namespace App\Core\Role\Actions;

use App\Core\Permission\PermissionRepository;
use App\Core\Role\RoleInput;
use App\Core\Role\RolePolicy;
use App\Core\Role\RoleRepository;
use App\Helpers\Translate;
use App\Services\Core\CurrentActorProvider;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class UpdateAction
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

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')]
        int $id,
        RoleInput $input,
    ): ResponseInterface {
        if (!$this->rolePolicy->canUpdate()) {
            return $this->webAction->forbidden();
        }

        $role = $this->roleRepository->findById($id);

        if ($role === null) {
            return $this->webAction->notFound();
        }

        $permissionGroups = $this->permissionRepository->findGroupedForRoleAssignment();
        $input->fill($role->toArray());
        $input->setPermissionIds($this->roleRepository->getPermissionIds($id));

        if ($this->webAction->isPost($request)) {
            $input->fill($request->getParsedBody());
            $input->id = $id;

            $result = $input->validateUpdate();
            $errors = $result->getErrorMessagesIndexedByProperty();
            $errors = $this->validatePermissionSelection($input, $errors);

            if (($errors['code'] ?? []) === []
                && $input->code !== null
                && $input->code !== ''
                && $this->roleRepository->codeExists($input->code, $id)
            ) {
                $errors['code'][] = Translate::t('Questo codice e gia assegnato a un altro ruolo.');
            }

            if (($errors['name'] ?? []) === []
                && $input->name !== null
                && $input->name !== ''
                && $this->roleRepository->nameExists($input->name, $id)
            ) {
                $errors['name'][] = Translate::t('Questo nome e gia assegnato a un altro ruolo.');
            }

            if ($errors === []) {
                $this->roleRepository->updateWithPermissions(
                    $input->toRole($role, $this->currentActorProvider->id()),
                    $input->permissionIds,
                );
                $this->flash->set('success', Translate::t('Ruolo aggiornato con successo.'));

                return $this->webAction->redirectToView('role', $id);
            }

            return $this->viewRenderer->render('form', [
                'mode' => 'update',
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
                'backUrl' => $this->webAction->updateBackUrl('role', $id, '/role'),
                'permissionGroups' => $permissionGroups,
            ]);
        }

        $backUrl = $this->webAction->rememberUpdateBackUrl('role', $id, $request, '/role');

        return $this->viewRenderer->render('form', [
            'mode' => 'update',
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

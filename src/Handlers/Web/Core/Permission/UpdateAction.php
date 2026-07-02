<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\Permission;

use App\Data\Core\Permission\PermissionGroupRepository;
use App\Data\Core\Permission\PermissionInput;
use App\Data\Core\Permission\PermissionPolicy;
use App\Data\Core\Permission\PermissionRepository;
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
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private PermissionRepository $permissionRepository,
        private PermissionGroupRepository $permissionGroupRepository,
        private PermissionPolicy $permissionPolicy,
        private CurrentActorProvider $currentActorProvider,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {}

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')]
        int $id,
        PermissionInput $input,
    ): ResponseInterface {
        if (!$this->permissionPolicy->canUpdate()) {
            return $this->webAction->forbidden();
        }

        $permission = $this->permissionRepository->findById($id);

        if ($permission === null) {
            return $this->webAction->notFound();
        }

        $input->fill($permission->toArray());
        $groupOptions = $this->permissionGroupRepository->findSelectableOptions();

        if ($this->webAction->isPost($request)) {
            $input->fill($request->getParsedBody());
            $input->id = $id;
            $result = $input->validateUpdate();
            $errors = $result->getErrorMessagesIndexedByProperty();

            if (($errors['groupId'] ?? []) === [] && $input->groupId !== null) {
                $group = $this->permissionGroupRepository->findById($input->groupId);

                if ($group === null) {
                    $errors['groupId'][] = Translate::t('Il gruppo selezionato non esiste.');
                } else {
                    $input->normalizeForGroup($group);
                    $errors = $input->validateUpdate()->getErrorMessagesIndexedByProperty();
                }
            }

            if (($errors['code'] ?? []) === []
                && ($errors['groupId'] ?? []) === []
                && $input->code !== null
                && $input->code !== ''
                && $this->permissionRepository->codeExists($input->code, $id)
            ) {
                $errors['code'][] = Translate::t('Questo codice e gia assegnato a un altro permesso.');
            }

            if (($errors['name'] ?? []) === []
                && $input->name !== null
                && $input->name !== ''
                && $this->permissionRepository->nameExists($input->name, $id)
            ) {
                $errors['name'][] = Translate::t('Questo nome e gia assegnato a un altro permesso.');
            }

            if ($errors === []) {
                $this->permissionRepository->update(
                    $input->toPermission($permission, $this->currentActorProvider->id()),
                );
                $this->flash->set('success', Translate::t('Permesso aggiornato con successo.'));

                return $this->webAction->redirectToView('permission', $id);
            }

            return $this->viewRenderer->render('core/permission/form', [
                'mode' => 'update',
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
                'groupOptions' => $groupOptions,
                'backUrl' => $this->webAction->updateBackUrl('permission', $id, '/permission'),
            ]);
        }

        $backUrl = $this->webAction->rememberUpdateBackUrl('permission', $id, $request, '/permission');

        return $this->viewRenderer->render('core/permission/form', [
            'mode' => 'update',
            'input' => $input,
            'errors' => [],
            'validated' => false,
            'groupOptions' => $groupOptions,
            'backUrl' => $backUrl,
        ]);
    }
}

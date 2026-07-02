<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\Permission;

use App\Data\Core\Permission\PermissionGroupRepository;
use App\Data\Core\Permission\PermissionInput;
use App\Data\Core\Permission\PermissionPolicy;
use App\Data\Core\Permission\PermissionRepository;
use App\Services\Core\CurrentActorProvider;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class CreateAction
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

    public function __invoke(ServerRequestInterface $request, PermissionInput $input): ResponseInterface
    {
        if (!$this->permissionPolicy->canCreate()) {
            return $this->webAction->forbidden();
        }

        $groupOptions = $this->permissionGroupRepository->findSelectableOptions();

        if ($this->webAction->isPost($request)) {
            $input->fill($request->getParsedBody());

            $result = $input->validateCreate();
            $errors = $result->getErrorMessagesIndexedByProperty();

            if (($errors['groupId'] ?? []) === [] && $input->groupId !== null) {
                $group = $this->permissionGroupRepository->findById($input->groupId);

                if ($group === null) {
                    $errors['groupId'][] = 'Il gruppo selezionato non esiste.';
                } else {
                    $input->normalizeForGroup($group);
                    $errors = $input->validateCreate()->getErrorMessagesIndexedByProperty();
                }
            }

            if (($errors['code'] ?? []) === []
                && ($errors['groupId'] ?? []) === []
                && $input->code !== null
                && $input->code !== ''
                && $this->permissionRepository->codeExists($input->code)
            ) {
                $errors['code'][] = 'Questo codice e gia assegnato a un altro permesso.';
            }

            if (($errors['name'] ?? []) === []
                && $input->name !== null
                && $input->name !== ''
                && $this->permissionRepository->nameExists($input->name)
            ) {
                $errors['name'][] = 'Questo nome e gia assegnato a un altro permesso.';
            }

            if ($errors === []) {
                $id = $this->permissionRepository->create(
                    $input->toPermission(actorId: $this->currentActorProvider->id()),
                );
                $this->flash->set('success', 'Permesso creato con successo.');

                return $this->webAction->redirectToView('permission', $id);
            }

            return $this->viewRenderer->render('core/permission/form', [
                'mode' => 'create',
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
                'groupOptions' => $groupOptions,
                'backUrl' => $this->webAction->createBackUrl('permission', '/permission'),
            ]);
        }

        $backUrl = $this->webAction->rememberCreateBackUrl('permission', $request, '/permission');

        return $this->viewRenderer->render('core/permission/form', [
            'mode' => 'create',
            'input' => $input,
            'errors' => [],
            'validated' => false,
            'groupOptions' => $groupOptions,
            'backUrl' => $backUrl,
        ]);
    }
}

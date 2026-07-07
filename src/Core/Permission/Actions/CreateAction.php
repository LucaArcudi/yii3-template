<?php

declare(strict_types=1);

namespace App\Core\Permission\Actions;

use App\Core\PermissionGroup\PermissionGroupRepository;
use App\Core\Permission\PermissionInput;
use App\Core\Permission\PermissionPolicy;
use App\Core\Permission\PermissionRepository;
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
        private PermissionRepository $permissionRepository,
        private PermissionGroupRepository $permissionGroupRepository,
        private PermissionPolicy $permissionPolicy,
        private CurrentActorProvider $currentActorProvider,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/Permission/views');
    }

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
                    $errors['groupId'][] = Translate::t('Il gruppo selezionato non esiste.');
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
                $errors['code'][] = Translate::t('Questo codice e gia assegnato a un altro permesso.');
            }

            if (($errors['name'] ?? []) === []
                && $input->name !== null
                && $input->name !== ''
                && $this->permissionRepository->nameExists($input->name)
            ) {
                $errors['name'][] = Translate::t('Questo nome e gia assegnato a un altro permesso.');
            }

            if ($errors === []) {
                $id = $this->permissionRepository->create(
                    $input->toPermission(actorId: $this->currentActorProvider->id()),
                );
                $this->flash->set('success', Translate::t('Permesso creato con successo.'));

                return $this->webAction->redirectToView('permission', $id);
            }

            return $this->viewRenderer->render('form', [
                'mode' => 'create',
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
                'groupOptions' => $groupOptions,
                'backUrl' => $this->webAction->createBackUrl('permission', '/permission'),
            ]);
        }

        $backUrl = $this->webAction->rememberCreateBackUrl('permission', $request, '/permission');

        return $this->viewRenderer->render('form', [
            'mode' => 'create',
            'input' => $input,
            'errors' => [],
            'validated' => false,
            'groupOptions' => $groupOptions,
            'backUrl' => $backUrl,
        ]);
    }
}

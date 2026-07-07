<?php

declare(strict_types=1);

namespace App\Core\PermissionGroup\Actions;

use App\Core\PermissionGroup\PermissionGroupInput;
use App\Core\PermissionGroup\PermissionGroupPolicy;
use App\Core\PermissionGroup\PermissionGroupRepository;
use App\Helpers\Translate;
use App\Services\Core\CurrentActorProvider;
use App\Services\Core\WebActionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class CreateAction
{
    private WebViewRenderer $viewRenderer;

    public function __construct(
        WebViewRenderer $viewRenderer,
        private PermissionGroupRepository $permissionGroupRepository,
        private PermissionGroupPolicy $permissionGroupPolicy,
        private CurrentActorProvider $currentActorProvider,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {
        $this->viewRenderer = $viewRenderer->withViewPath('@src/Core/PermissionGroup/views');
    }

    public function __invoke(ServerRequestInterface $request, PermissionGroupInput $input): ResponseInterface
    {
        if (!$this->permissionGroupPolicy->canCreate()) {
            return $this->webAction->forbidden();
        }

        if ($this->webAction->isPost($request)) {
            $input->fill($request->getParsedBody());
            $result = $input->validateCreate();
            $errors = $result->getErrorMessagesIndexedByProperty();

            $this->validateUnique($input, $errors);

            if ($errors === []) {
                $group = $this->permissionGroupRepository->create(
                    $input->toGroup(actorId: $this->currentActorProvider->id()),
                );
                $this->flash->set('success', Translate::t('Gruppo permessi creato con successo.'));

                return $this->webAction->redirectToView('permission-group', (int) $group->id);
            }

            return $this->viewRenderer->render('form', [
                'mode' => 'create',
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
                'backUrl' => $this->webAction->createBackUrl('permission-group', '/permission-group'),
            ]);
        }

        $backUrl = $this->webAction->rememberCreateBackUrl('permission-group', $request, '/permission-group');

        return $this->viewRenderer->render('form', [
            'mode' => 'create',
            'input' => $input,
            'errors' => [],
            'validated' => false,
            'backUrl' => $backUrl,
        ]);
    }

    private function validateUnique(PermissionGroupInput $input, array &$errors): void
    {
        if (($errors['name'] ?? []) === []
            && $input->name !== null
            && $input->name !== ''
            && $this->permissionGroupRepository->nameExists($input->name)
        ) {
            $errors['name'][] = Translate::t('Questo nome e gia assegnato a un altro gruppo.');
        }

        if (($errors['code'] ?? []) === []
            && $input->code !== null
            && $input->code !== ''
            && $this->permissionGroupRepository->codeExists($input->code)
        ) {
            $errors['code'][] = Translate::t('Questo codice e gia assegnato a un altro gruppo.');
        }
    }
}

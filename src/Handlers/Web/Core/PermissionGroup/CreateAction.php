<?php

declare(strict_types=1);

namespace App\Handlers\Web\Core\PermissionGroup;

use App\Data\Core\Permission\PermissionGroupInput;
use App\Data\Core\Permission\PermissionGroupPolicy;
use App\Data\Core\Permission\PermissionGroupRepository;
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
        private PermissionGroupRepository $permissionGroupRepository,
        private PermissionGroupPolicy $permissionGroupPolicy,
        private CurrentActorProvider $currentActorProvider,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {}

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
                $this->flash->set('success', 'Gruppo permessi creato con successo.');

                return $this->webAction->redirectToView('permission-group', (int) $group->id);
            }

            return $this->viewRenderer->render('core/permission-group/form', [
                'mode' => 'create',
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
                'backUrl' => $this->webAction->createBackUrl('permission-group', '/permission-group'),
            ]);
        }

        $backUrl = $this->webAction->rememberCreateBackUrl('permission-group', $request, '/permission-group');

        return $this->viewRenderer->render('core/permission-group/form', [
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
            $errors['name'][] = 'Questo nome e gia assegnato a un altro gruppo.';
        }

        if (($errors['code'] ?? []) === []
            && $input->code !== null
            && $input->code !== ''
            && $this->permissionGroupRepository->codeExists($input->code)
        ) {
            $errors['code'][] = 'Questo codice e gia assegnato a un altro gruppo.';
        }
    }
}

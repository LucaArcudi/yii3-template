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
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class UpdateAction
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private PermissionGroupRepository $permissionGroupRepository,
        private PermissionGroupPolicy $permissionGroupPolicy,
        private CurrentActorProvider $currentActorProvider,
        private FlashInterface $flash,
        private WebActionService $webAction,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')] int $id,
        PermissionGroupInput $input,
    ): ResponseInterface {
        if (!$this->permissionGroupPolicy->canUpdate()) {
            return $this->webAction->forbidden();
        }

        $group = $this->permissionGroupRepository->findById($id);

        if ($group === null) {
            return $this->webAction->notFound();
        }

        $input->fill($group->toArray());

        if ($this->webAction->isPost($request)) {
            $input->fill($request->getParsedBody());
            $input->id = $id;
            $result = $input->validateUpdate();
            $errors = $result->getErrorMessagesIndexedByProperty();

            $this->validateUnique($input, $errors, $id);

            if ($errors === []) {
                $this->permissionGroupRepository->update(
                    $input->toGroup($group, $this->currentActorProvider->id()),
                );
                $this->flash->set('success', 'Gruppo permessi aggiornato con successo.');

                return $this->webAction->redirectToView('permission-group', $id);
            }

            return $this->viewRenderer->render('core/permission-group/form', [
                'mode' => 'update',
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
                'backUrl' => $this->webAction->updateBackUrl('permission-group', $id, '/permission-group'),
            ]);
        }

        $backUrl = $this->webAction->rememberUpdateBackUrl('permission-group', $id, $request, '/permission-group');

        return $this->viewRenderer->render('core/permission-group/form', [
            'mode' => 'update',
            'input' => $input,
            'errors' => [],
            'validated' => false,
            'backUrl' => $backUrl,
        ]);
    }

    private function validateUnique(PermissionGroupInput $input, array &$errors, int $id): void
    {
        if (($errors['name'] ?? []) === []
            && $input->name !== null
            && $input->name !== ''
            && $this->permissionGroupRepository->nameExists($input->name, $id)
        ) {
            $errors['name'][] = 'Questo nome e gia assegnato a un altro gruppo.';
        }

        if (($errors['code'] ?? []) === []
            && $input->code !== null
            && $input->code !== ''
            && $this->permissionGroupRepository->codeExists($input->code, $id)
        ) {
            $errors['code'][] = 'Questo codice e gia assegnato a un altro gruppo.';
        }
    }
}

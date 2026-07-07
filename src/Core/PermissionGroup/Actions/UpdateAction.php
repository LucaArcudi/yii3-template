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
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final readonly class UpdateAction
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

    public function __invoke(
        ServerRequestInterface $request,
        #[RouteArgument('id')]
        int $id,
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
                $this->flash->set('success', Translate::t('Gruppo permessi aggiornato con successo.'));

                return $this->webAction->redirectToView('permission-group', $id);
            }

            return $this->viewRenderer->render('form', [
                'mode' => 'update',
                'input' => $input,
                'errors' => $errors,
                'validated' => true,
                'backUrl' => $this->webAction->updateBackUrl('permission-group', $id, '/permission-group'),
            ]);
        }

        $backUrl = $this->webAction->rememberUpdateBackUrl('permission-group', $id, $request, '/permission-group');

        return $this->viewRenderer->render('form', [
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
            $errors['name'][] = Translate::t('Questo nome e gia assegnato a un altro gruppo.');
        }

        if (($errors['code'] ?? []) === []
            && $input->code !== null
            && $input->code !== ''
            && $this->permissionGroupRepository->codeExists($input->code, $id)
        ) {
            $errors['code'][] = Translate::t('Questo codice e gia assegnato a un altro gruppo.');
        }
    }
}

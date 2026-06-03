<?php

declare(strict_types=1);

use App\Data\Core\Permission\PermissionGroupInput;
use App\Widgets\BackButton;
use App\Widgets\Forms\FormCard;
use App\Widgets\Forms\FormTheme;
use App\Widgets\Inputs\TextInput;
use Yiisoft\Form\PureField\Field;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\Csrf;

/** @var PermissionGroupInput $input */
/** @var array $errors */
/** @var string $mode */
/** @var Csrf $csrf */
/** @var UrlGeneratorInterface $urlGenerator */
/** @var string $backUrl */
/** @var bool $validated */

FormTheme::boot();

$action = $mode === 'update'
    ? $urlGenerator->generate('permission-group/update', ['id' => (int) $input->id])
    : $urlGenerator->generate('permission-group/create');

$title = $mode === 'update' ? 'Modifica gruppo permessi' : 'Nuovo gruppo permessi';

$this->setTitle($title);
$this->setParameter('pageIcon', 'fa-solid fa-layer-group');
$this->setParameter('breadcrumbs', [
    ['label' => 'Dashboard', 'url' => '/'],
    ['label' => 'Gruppi permessi', 'url' => '/permission-group'],
    ['label' => $title],
]);
$this->setParameter('pageActions', BackButton::render($backUrl));

$validationRules = $input->getRules();

$form = '';
$form .= '<form method="post" action="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '" class="app-validation-form" data-validated="' . ($validated ? '1' : '0') . '">';
$form .= $csrf->hiddenInput();

if (($errors[''] ?? []) !== []) {
    $form .= (string) Field::errorSummary(['' => $errors['']])
        ->onlyCommonErrors();
}

$form .= TextInput::render(
    name: 'name',
    label: 'Nome',
    value: (string) $input->name,
    placeholder: 'Es. User',
    icon: 'fa-solid fa-layer-group',
    validationRules: $validationRules['name'] ?? [],
    validationErrors: $errors['name'] ?? [],
    validated: $validated,
);

$form .= TextInput::render(
    name: 'code',
    label: 'Codice',
    value: (string) $input->code,
    placeholder: 'Es. USER',
    icon: 'fa-solid fa-key',
    hint: 'Il codice identifica il gruppo nelle autorizzazioni, ad esempio USER + ACCESS.',
    validationRules: $validationRules['code'] ?? [],
    validationErrors: $errors['code'] ?? [],
    validated: $validated,
);

$form .= '<div class="app-form-actions mt-4">';
$form .= Field::submitButton('Salva')->addButtonClass('px-4');
$form .= '</div>';

$form .= '</form>';

echo FormCard::render(
    title: $title,
    formHtml: $form,
    variant: 'info',
);

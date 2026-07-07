<?php

declare(strict_types=1);

use App\Core\Role\RoleInput;
use App\Shared\Helpers\Translate;
use App\Shared\Widgets\BackButton;
use App\Shared\Widgets\Forms\FormCard;
use App\Shared\Widgets\Forms\FormTheme;
use App\Shared\Widgets\Inputs\CheckboxGroupInput;
use App\Shared\Widgets\Inputs\TextInput;
use Yiisoft\Form\PureField\Field;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\Csrf;

/** @var RoleInput $input */
/** @var array $errors */
/** @var string $mode */
/** @var Csrf $csrf */
/** @var UrlGeneratorInterface $urlGenerator */
/** @var string $backUrl */
/** @var bool $validated */
/** @var array $permissionGroups */

FormTheme::boot();

$action = $mode === 'update'
    ? $urlGenerator->generate('role/update', ['id' => (int) $input->id])
    : $urlGenerator->generate('role/create');

$title = $mode === 'update' ? Translate::t('Modifica ruolo') : Translate::t('Nuovo ruolo');

$this->setTitle($title);
$this->setParameter('pageIcon', 'pe-7s-id');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Ruoli'), 'url' => '/role'],
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
    label: Translate::t('Nome'),
    value: (string) $input->name,
    placeholder: Translate::t('Inserisci il nome descrittivo del ruolo'),
    icon: 'fa-solid fa-user-shield',
    validationRules: $validationRules['name'] ?? [],
    validationErrors: $errors['name'] ?? [],
    validated: $validated,
);

$form .= TextInput::render(
    name: 'code',
    label: Translate::t('Codice'),
    value: (string) $input->code,
    placeholder: Translate::t('Es. ADMIN'),
    icon: 'fa-solid fa-id-badge',
    validationRules: $validationRules['code'] ?? [],
    validationErrors: $errors['code'] ?? [],
    validated: $validated,
);

$form .= CheckboxGroupInput::render(
    name: 'permission_ids',
    label: Translate::t('Permessi associati'),
    groups: $permissionGroups,
    selectedValues: $input->permissionIds,
    hint: Translate::t('Seleziona i permessi da associare a questo ruolo. I permessi determinano le azioni che un utente con questo ruolo può eseguire all\'interno dell\'applicazione.'),
    validationErrors: $errors['permissionIds'] ?? [],
    validated: $validated,
);

$form .= '<div class="app-form-actions mt-4">';
$form .= Field::submitButton(Translate::t('Salva'))->addButtonClass('px-4');
$form .= '</div>';

$form .= '</form>';

echo FormCard::render(
    title: $title,
    formHtml: $form,
    variant: 'warning',
);

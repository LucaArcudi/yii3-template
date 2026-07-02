<?php

declare(strict_types=1);

use App\Data\Core\User\UserEntity;
use App\Data\Core\User\UserInput;
use App\Helpers\Translate;
use App\Widgets\BackButton;
use App\Widgets\Forms\FormCard;
use App\Widgets\Forms\FormTheme;
use App\Widgets\Inputs\EmailInput;
use App\Widgets\Inputs\MultiSelectInput;
use App\Widgets\Inputs\PasswordInput;
use App\Widgets\Inputs\SelectInput;
use App\Widgets\Inputs\TextInput;
use Yiisoft\Form\PureField\Field;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\Csrf;

/** @var UserInput $input */
/** @var array $errors */
/** @var string $mode */
/** @var Csrf $csrf */
/** @var UrlGeneratorInterface $urlGenerator */
/** @var string $backUrl */
/** @var bool $validated */
/** @var array $roleOptions */

FormTheme::boot();

$action = $mode === 'update'
    ? $urlGenerator->generate('user/update', ['id' => (int) $input->id])
    : $urlGenerator->generate('user/create');

$title = $mode === 'update' ? Translate::t('Modifica utente') : Translate::t('Nuovo utente');

$this->setTitle($title);
$this->setParameter('pageIcon', 'pe-7s-users');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Utenti'), 'url' => '/user'],
    ['label' => $title],
]);
$this->setParameter('pageActions', BackButton::render($backUrl));

$validationRules = $mode === 'create'
    ? $input->getCreateRules()
    : $input->getUpdateRules();

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
    placeholder: Translate::t('Nome visualizzato nel gestionale'),
    icon: 'fa-regular fa-user',
    validationRules: $validationRules['name'] ?? [],
    validationErrors: $errors['name'] ?? [],
    validated: $validated,
);

$form .= EmailInput::render(
    name: 'email',
    label: 'Email',
    value: (string) $input->email,
    placeholder: Translate::t('nome@azienda.it'),
    icon: 'fa-regular fa-envelope',
    inputAttributes: [
        'autocomplete' => 'email',
    ],
    validationRules: $validationRules['email'] ?? [],
    validationErrors: $errors['email'] ?? [],
    validated: $validated,
);

$form .= PasswordInput::render(
    name: 'password',
    label: 'Password',
    placeholder: $mode === 'update' ? Translate::t('Lascia vuota per non cambiarla') : Translate::t('Minimo 8 caratteri'),
    icon: 'fa-solid fa-lock',
    hint: $mode === 'update' ? Translate::t('Compilala solo se vuoi impostare una nuova password.') : null,
    inputAttributes: [
        'autocomplete' => $mode === 'update' ? 'new-password' : 'new-password',
    ],
    validationRules: $validationRules['password'] ?? [],
    validationErrors: $errors['password'] ?? [],
    validated: $validated,
);

$form .= PasswordInput::render(
    name: 'password_repeat',
    label: Translate::t('Ripeti password'),
    placeholder: $mode === 'update' ? Translate::t('Ripeti la nuova password') : Translate::t('Ripeti la password'),
    icon: 'fa-solid fa-lock',
    hint: $mode === 'update' ? Translate::t('Da compilare solo quando imposti una nuova password.') : null,
    inputAttributes: [
        'autocomplete' => 'new-password',
        'data-match-field' => 'password',
        'data-match-message' => Translate::t('Le password non coincidono.'),
    ],
    validationRules: $validationRules['passwordRepeat'] ?? [],
    validationErrors: $errors['passwordRepeat'] ?? [],
    validated: $validated,
);

$form .= SelectInput::render(
    name: 'status',
    label: Translate::t('Stato'),
    value: (string) ($input->status ?? UserEntity::STATUS_ACTIVE),
    options: UserEntity::statusOptions(),
    prompt: Translate::t('Seleziona lo stato'),
    icon: 'fa-solid fa-signal',
    validationRules: $validationRules['status'] ?? [],
    validationErrors: $errors['status'] ?? [],
    validated: $validated,
);

$form .= MultiSelectInput::render(
    name: 'role_ids',
    label: Translate::t('Ruoli associati'),
    values: array_map(static fn(int $id): string => (string) $id, $input->roleIds),
    options: $roleOptions,
    icon: 'fa-solid fa-user-tag',
    hint: $roleOptions === []
        ? Translate::t('Nessun ruolo disponibile. Crea prima almeno un ruolo.')
        : Translate::t('Seleziona uno o piu ruoli, rimuovili dai tag oppure usa Seleziona tutti e Svuota dal menu.'),
    placeholder: Translate::t('Seleziona i ruoli da associare'),
    validationErrors: $errors['roleIds'] ?? [],
    validated: $validated,
);

$form .= '<div class="app-form-actions mt-4">';
$form .= Field::submitButton(Translate::t('Salva'))->addButtonClass('px-4');
$form .= '</div>';

$form .= '</form>';

echo FormCard::render(
    title: $title,
    formHtml: $form,
    variant: 'secondary',
);

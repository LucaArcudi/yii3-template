<?php

declare(strict_types=1);

use App\Core\User\UserInput;
use App\Shared\Helpers\Translate;
use App\Shared\Widgets\BackButton;
use App\Shared\Widgets\Card;
use App\Shared\Widgets\Crud\CrudActions;
use App\Shared\Widgets\Forms\FormCard;
use App\Shared\Widgets\Forms\FormTheme;
use App\Shared\Widgets\Inputs\EmailInput;
use App\Shared\Widgets\Inputs\PasswordInput;
use App\Shared\Widgets\Inputs\TextInput;
use Yiisoft\Form\PureField\Field;
use Yiisoft\Html\Html;
use Yiisoft\Yii\View\Renderer\Csrf;

/** @var UserInput $input */
/** @var array $profileErrors */
/** @var array $emailErrors */
/** @var Csrf $csrf */
/** @var bool $profileValidated */
/** @var bool $emailValidated */
/** @var int $userId */
/** @var string $currentEmail */

FormTheme::boot();

$profileErrors ??= $errors ?? [];
$emailErrors ??= [];
$profileValidated ??= $validated ?? false;
$emailValidated ??= false;
$currentEmail ??= (string) $input->email;

$this->setTitle(Translate::t('Gestione profilo'));
$this->setParameter('pageIcon', 'pe-7s-user');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Gestione profilo')],
]);
$this->setParameter(
    'pageActions',
    (string) Html::div(
        BackButton::render('/')
        . (string) Html::a(
            (string) Html::i('', ['class' => 'fa-solid fa-key me-1']) . Html::encode(Translate::t('Cambio password')),
            '/change-password',
            ['class' => 'btn-shadow btn btn-primary'],
        )->encode(false),
        ['class' => 'app-page-actions'],
    )->encode(false),
);

$deleteModalId = 'profile-delete-modal';

$deleteModalBody = CrudActions::deleteBody(
    Translate::t('Stai eliminando il tuo profilo {name}. Dopo la conferma verrai disconnesso e il record non sara piu recuperabile.', ['name' => '<strong>' . Html::encode((string) $input->name) . '</strong>']),
    [
        Translate::t('ID record') => '#' . $userId,
        'Email' => Html::encode($currentEmail),
    ],
);

$this->setParameter(
    'pageModals',
    CrudActions::deleteModal(
        id: $deleteModalId,
        title: Translate::t('Elimina profilo'),
        action: '/profile/delete',
        body: $deleteModalBody,
        csrf: $csrf,
    ),
);

$profileValidationRules = $input->getProfileRules();
$emailValidationRules = $input->getEmailChangeRules();

$form = '';
$form .= '<form method="post" action="/profile" class="app-validation-form" data-validated="' . ($profileValidated ? '1' : '0') . '">';
$form .= $csrf->hiddenInput();
$form .= '<input type="hidden" name="form" value="profile">';

if (($profileErrors[''] ?? []) !== []) {
    $form .= (string) Field::errorSummary(['' => $profileErrors['']])
        ->onlyCommonErrors();
}

$form .= TextInput::render(
    name: 'name',
    label: Translate::t('Nome'),
    value: (string) $input->name,
    placeholder: Translate::t('Nome visualizzato nel gestionale'),
    icon: 'fa-regular fa-user',
    validationRules: $profileValidationRules['name'] ?? [],
    validationErrors: $profileErrors['name'] ?? [],
    validated: $profileValidated,
);

$form .= '<div class="app-form-actions mt-4">';
$form .= Field::submitButton(Translate::t('Salva profilo'))->addButtonClass('px-4');
$form .= '</div>';
$form .= '</form>';

echo FormCard::render(
    title: Translate::t('Dati profilo'),
    formHtml: $form,
    variant: 'secondary',
);

$emailForm = '';
$emailForm .= '<form method="post" action="/profile" class="app-validation-form" data-validated="' . ($emailValidated ? '1' : '0') . '">';
$emailForm .= $csrf->hiddenInput();
$emailForm .= '<input type="hidden" name="form" value="email">';

if (($emailErrors[''] ?? []) !== []) {
    $emailForm .= (string) Field::errorSummary(['' => $emailErrors['']])
        ->onlyCommonErrors();
}

$emailForm .= (string) Html::div(
    (string) Html::div(Translate::t('Email attuale'), ['class' => 'app-task-view__meta-label'])
    . (string) Html::div(Html::encode($currentEmail), ['class' => 'app-task-view__meta-value']),
    ['class' => 'app-task-view__meta-grid mb-3'],
)->encode(false);

$emailForm .= EmailInput::render(
    name: 'email',
    label: Translate::t('Nuova email'),
    value: (string) $input->email,
    placeholder: Translate::t('nome@azienda.it'),
    icon: 'fa-regular fa-envelope',
    inputAttributes: [
        'autocomplete' => 'email',
    ],
    validationRules: $emailValidationRules['email'] ?? [],
    validationErrors: $emailErrors['email'] ?? [],
    validated: $emailValidated,
);

$emailForm .= PasswordInput::render(
    name: 'current_password',
    label: Translate::t('Password attuale'),
    placeholder: Translate::t('Password attuale'),
    icon: 'fa-solid fa-lock',
    inputAttributes: [
        'autocomplete' => 'current-password',
    ],
    validationRules: $emailValidationRules['currentPassword'] ?? [],
    validationErrors: $emailErrors['currentPassword'] ?? [],
    validated: $emailValidated,
);

$emailForm .= '<div class="app-form-actions mt-4">';
$emailForm .= Field::submitButton(Translate::t('Aggiorna email'))->addButtonClass('px-4');
$emailForm .= '</div>';
$emailForm .= '</form>';

echo FormCard::render(
    title: Translate::t('Cambio email'),
    formHtml: $emailForm,
    variant: 'info',
);

echo Card::render(
    title: Translate::t('Zona pericolosa'),
    body: (string) Html::div(
        (string) Html::p(
            Translate::t('Puoi eliminare il tuo profilo. L\'operazione chiude la sessione e rimuove l\'utente.'),
            ['class' => 'text-muted mb-3'],
        )
        . CrudActions::deletePageTrigger($deleteModalId, label: Translate::t('Elimina il mio profilo')),
        ['class' => 'app-task-view__danger-zone'],
    )->encode(false),
    variant: 'danger',
    icon: 'fa-solid fa-triangle-exclamation',
);

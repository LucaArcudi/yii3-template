<?php

declare(strict_types=1);

use App\Helpers\Translate;
use App\Widgets\BackButton;
use App\Widgets\Forms\FormCard;
use App\Widgets\Forms\FormTheme;
use App\Widgets\Inputs\PasswordInput;
use Yiisoft\Form\PureField\Field;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var \App\Core\User\ChangePasswordInput $input
 * @var array $errors
 * @var bool $validated
 * @var bool $requiresCurrentPassword
 * @var bool $tokenMode
 * @var string|null $reason
 */

$this->setTitle(Translate::t('Cambio password'));
FormTheme::boot();

$this->setParameter('pageIcon', 'pe-7s-lock');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Cambio password')],
]);
$this->setParameter('pageActions', BackButton::render('/'));

$validationRules = $input->getRules($requiresCurrentPassword);
$subtitle = $reason === 'expired'
    ? Translate::t('La password è scaduta. Scegline una nuova per proseguire.')
    : ($tokenMode ? Translate::t('Imposta una nuova password usando il link ricevuto via email.') : Translate::t('Aggiorna la password del tuo account.'));

$form = '';
$form .= '<div class="text-muted mb-3">' . Html::encode($subtitle) . '</div>';
$form .= '<form method="post" action="/change-password" class="app-validation-form" data-validated="' . ($validated ? '1' : '0') . '">';
$form .= $csrf->hiddenInput();

if ($input->token !== null && $input->token !== '') {
    $form .= Html::hiddenInput('token', $input->token);
}

if (($errors[''] ?? []) !== []) {
    $form .= (string) Field::errorSummary(['' => $errors['']])
        ->onlyCommonErrors();
}

if ($requiresCurrentPassword) {
    $form .= PasswordInput::render(
        name: 'current_password',
        label: Translate::t('Password attuale'),
        placeholder: Translate::t('Inserisci la password attuale'),
        icon: 'fa-solid fa-lock',
        inputAttributes: [
            'autocomplete' => 'current-password',
            'autofocus' => true,
        ],
        validationRules: $validationRules['currentPassword'] ?? [],
        validationErrors: $errors['currentPassword'] ?? [],
        validated: $validated,
    );
}

$form .= PasswordInput::render(
    name: 'password',
    label: Translate::t('Nuova password'),
    placeholder: Translate::t('Minimo 8 caratteri'),
    icon: 'fa-solid fa-key',
    inputAttributes: [
        'autocomplete' => 'new-password',
        'autofocus' => !$requiresCurrentPassword,
    ],
    validationRules: $validationRules['password'] ?? [],
    validationErrors: $errors['password'] ?? [],
    validated: $validated,
);

$form .= PasswordInput::render(
    name: 'password_repeat',
    label: Translate::t('Ripeti nuova password'),
    placeholder: Translate::t('Conferma la nuova password'),
    icon: 'fa-solid fa-shield-halved',
    inputAttributes: [
        'autocomplete' => 'new-password',
        'data-match-field' => 'password',
        'data-match-message' => Translate::t('Le password non coincidono.'),
    ],
    validationRules: $validationRules['passwordRepeat'] ?? [],
    validationErrors: $errors['passwordRepeat'] ?? [],
    validated: $validated,
);

$form .= '<div class="app-form-actions mt-4">';
$form .= Field::submitButton(Translate::t('Aggiorna password'))->addButtonClass('px-4');
$form .= '</div>';
$form .= '</form>';

if ($tokenMode) {
    echo '<div class="text-center mb-4">';
    echo '<h3 class="mb-1">' . Html::encode(Translate::t('Cambio password')) . '</h3>';
    echo '<p class="text-muted mb-0">' . Html::encode($subtitle) . '</p>';
    echo '</div>';
    echo $form;
    echo '<div class="text-center text-muted mt-3"><a href="/login">' . Html::encode(Translate::t('Torna al login')) . '</a></div>';

    return;
}

echo FormCard::render(
    title: Translate::t('Cambio password'),
    formHtml: $form,
    variant: 'secondary',
);

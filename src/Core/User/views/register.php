<?php

declare(strict_types=1);

use App\Shared\Helpers\Translate;
use App\Shared\Widgets\Forms\FormTheme;
use App\Shared\Widgets\Inputs\EmailInput;
use App\Shared\Widgets\Inputs\PasswordInput;
use App\Shared\Widgets\Inputs\TextInput;
use Yiisoft\Form\PureField\Field;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var \App\Core\User\RegisterInput $input
 * @var array $errors
 * @var bool $validated
 * @var array{question: string} $captcha
 */

$this->setTitle(Translate::t('Registrazione'));
FormTheme::boot();

$validationRules = $input->getRules();
?>
<div class="text-center mb-4">
    <h3 class="mb-1"><?= Translate::t('Crea il tuo account') ?></h3>
    <p class="text-muted mb-0"><?= Translate::t('Compila i campi per accedere alla base gestionale.') ?></p>
</div>

<div class="alert alert-warning border mb-3" role="alert">
    <div class="fw-semibold mb-1"><?= Translate::t('Conserva l\'email usata per registrarti.') ?></div>
    <div>
        <?= Translate::t('L\'email è l\'identificativo del tuo account: se la dimentichi non sarà possibile autenticarsi.') ?>
    </div>
</div>

<form method="post" action="/register" class="app-validation-form" data-validated="<?= $validated ? '1' : '0' ?>">
    <?= $csrf->hiddenInput() ?>
    <?php if (($errors[''] ?? []) !== []): ?>
        <?= Field::errorSummary(['' => $errors['']])->onlyCommonErrors() ?>
    <?php endif; ?>

    <?= TextInput::render(
        name: 'name',
        label: Translate::t('Nome'),
        value: (string) $input->name,
        placeholder: Translate::t('Come vuoi comparire nel gestionale'),
        icon: 'fa-regular fa-user',
        inputAttributes: [
            'autocomplete' => 'name',
            'autofocus' => true,
        ],
        validationRules: $validationRules['name'] ?? [],
        validationErrors: $errors['name'] ?? [],
        validated: $validated,
    ) ?>

    <?= EmailInput::render(
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
    ) ?>

    <?= PasswordInput::render(
        name: 'password',
        label: 'Password',
        placeholder: Translate::t('Minimo 8 caratteri'),
        icon: 'fa-solid fa-lock',
        inputAttributes: [
            'autocomplete' => 'new-password',
        ],
        validationRules: $validationRules['password'] ?? [],
        validationErrors: $errors['password'] ?? [],
        validated: $validated,
    ) ?>

    <?= PasswordInput::render(
        name: 'password_repeat',
        label: Translate::t('Ripeti password'),
        placeholder: Translate::t('Conferma la password'),
        icon: 'fa-solid fa-shield-halved',
        inputAttributes: [
            'autocomplete' => 'new-password',
            'data-match-field' => 'password',
            'data-match-message' => Translate::t('Le password non coincidono.'),
        ],
        validationRules: $validationRules['passwordRepeat'] ?? [],
        validationErrors: $errors['passwordRepeat'] ?? [],
        validated: $validated,
    ) ?>

    <div class="position-absolute overflow-hidden" style="left:-10000px;width:1px;height:1px;" aria-hidden="true">
        <label for="register-website">Sito web</label>
        <input type="text" name="website" id="register-website" tabindex="-1" autocomplete="off">
    </div>

    <?= TextInput::render(
        name: 'captcha',
        label: Translate::t('Verifica: {question}', ['question' => $captcha['question']]),
        value: '',
        placeholder: Translate::t('Risposta'),
        icon: 'fa-solid fa-shield-halved',
        inputAttributes: [
            'autocomplete' => 'off',
            'inputmode' => 'numeric',
        ],
        validationRules: $validationRules['captcha'] ?? [],
        validationErrors: $errors['captcha'] ?? [],
        validated: $validated,
    ) ?>

    <div class="d-grid">
        <?= Field::submitButton(Translate::t('Registrati'))->addButtonClass('w-100') ?>
    </div>
</form>

<div class="text-center text-muted mt-3">
    <?= Translate::t('Hai gia un account?') ?>
    <a href="/login"><?= Translate::t('Vai al login') ?></a>
</div>

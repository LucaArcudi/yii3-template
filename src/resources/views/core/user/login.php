<?php

declare(strict_types=1);

use App\Widgets\Forms\FormTheme;
use App\Widgets\Inputs\EmailInput;
use App\Widgets\Inputs\PasswordInput;
use Yiisoft\Form\PureField\Field;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var \App\Data\Core\User\LoginInput $input
 * @var array $errors
 * @var bool $validated
 */

$this->setTitle('Login');
FormTheme::boot();

$validationRules = $input->getRules();
?>
<div class="text-center mb-4">
    <h3 class="mb-1">Bentornato</h3>
    <p class="text-muted mb-0">Inserisci le tue credenziali per continuare.</p>
</div>

<form
    method="post"
    action="/login"
    class="app-validation-form"
    data-validated="<?= $validated ? '1' : '0' ?>"
>
    <?= $csrf->hiddenInput() ?>
    <?php if (($errors[''] ?? []) !== []): ?>
        <?= Field::errorSummary(['' => $errors['']])->onlyCommonErrors() ?>
    <?php endif; ?>

    <?= EmailInput::render(
        name: 'email',
        label: 'Email',
        value: (string) $input->email,
        placeholder: 'nome@azienda.it',
        icon: 'fa-regular fa-envelope',
        inputAttributes: [
            'autocomplete' => 'username',
            'autofocus' => true,
        ],
        validationRules: $validationRules['email'] ?? [],
        validationErrors: $errors['email'] ?? [],
        validated: $validated,
    ) ?>

    <?= PasswordInput::render(
        name: 'password',
        label: 'Password',
        placeholder: 'Inserisci la password',
        icon: 'fa-solid fa-lock',
        inputAttributes: [
            'autocomplete' => 'current-password',
        ],
        validationRules: $validationRules['password'] ?? [],
        validationErrors: $errors['password'] ?? [],
        validated: $validated,
    ) ?>

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div class="form-check mb-0">
            <input
                type="checkbox"
                name="remember_me"
                value="1"
                id="remember-me"
                class="form-check-input"
                <?= $input->rememberMe ? 'checked' : '' ?>
            >
            <label class="form-check-label" for="remember-me">Ricordami</label>
        </div>

        <a href="/forgot-password">Password dimenticata?</a>
    </div>

    <div class="d-grid">
        <?= Field::submitButton('Accedi')->addButtonClass('w-100') ?>
    </div>
</form>

<div class="text-center text-muted mt-3">
    Nessun account?
    <a href="/register">Registrati</a>
</div>

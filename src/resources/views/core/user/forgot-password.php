<?php

declare(strict_types=1);

use App\Widgets\Forms\FormTheme;
use App\Widgets\Inputs\EmailInput;
use Yiisoft\Form\PureField\Field;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var \App\Data\Core\User\ForgotPasswordInput $input
 * @var array $errors
 * @var bool $validated
 */

$this->setTitle('Password dimenticata');
FormTheme::boot();

$validationRules = $input->getRules();
?>
<div class="text-center mb-4">
    <h3 class="mb-1">Recupera password</h3>
    <p class="text-muted mb-0">Ti invieremo un link per impostarne una nuova.</p>
</div>

<form method="post" action="/forgot-password" class="app-validation-form" data-validated="<?= $validated ? '1' : '0' ?>">
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
            'autocomplete' => 'email',
            'autofocus' => true,
        ],
        validationRules: $validationRules['email'] ?? [],
        validationErrors: $errors['email'] ?? [],
        validated: $validated,
    ) ?>

    <div class="d-grid">
        <?= Field::submitButton('Invia link')->addButtonClass('w-100') ?>
    </div>
</form>

<div class="text-center text-muted mt-3">
    <a href="/login">Torna al login</a>
    <span class="mx-1">·</span>
    <a href="/forgot-email">Email dimenticata?</a>
</div>

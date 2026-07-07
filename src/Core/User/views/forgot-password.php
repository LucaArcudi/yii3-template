<?php

declare(strict_types=1);

use App\Helpers\Translate;
use App\Widgets\Forms\FormTheme;
use App\Widgets\Inputs\EmailInput;
use Yiisoft\Form\PureField\Field;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var \App\Core\User\ForgotPasswordInput $input
 * @var array $errors
 * @var bool $validated
 */

$this->setTitle(Translate::t('Password dimenticata'));
FormTheme::boot();

$validationRules = $input->getRules();
?>
<div class="text-center mb-4">
    <h3 class="mb-1"><?= Translate::t('Recupera password') ?></h3>
    <p class="text-muted mb-0"><?= Translate::t('Ti invieremo un link per impostarne una nuova.') ?></p>
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
        placeholder: Translate::t('nome@azienda.it'),
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
        <?= Field::submitButton(Translate::t('Invia link'))->addButtonClass('w-100') ?>
    </div>
</form>

<div class="text-center text-muted mt-3">
    <a href="/login"><?= Translate::t('Torna al login') ?></a>
    <span class="mx-1">·</span>
    <a href="/forgot-email"><?= Translate::t('Email dimenticata?') ?></a>
</div>

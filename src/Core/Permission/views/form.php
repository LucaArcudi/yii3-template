<?php

declare(strict_types=1);

use App\Core\Permission\PermissionInput;
use App\Shared\Helpers\Translate;
use App\Shared\Widgets\BackButton;
use App\Shared\Widgets\Forms\FormCard;
use App\Shared\Widgets\Forms\FormTheme;
use App\Shared\Widgets\Inputs\SelectInput;
use App\Shared\Widgets\Inputs\TextInput;
use Yiisoft\Form\PureField\Field;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\Csrf;

/** @var PermissionInput $input */
/** @var array $errors */
/** @var string $mode */
/** @var Csrf $csrf */
/** @var UrlGeneratorInterface $urlGenerator */
/** @var string $backUrl */
/** @var bool $validated */
/** @var array $groupOptions */

FormTheme::boot();

$action = $mode === 'update'
    ? $urlGenerator->generate('permission/update', ['id' => (int) $input->id])
    : $urlGenerator->generate('permission/create');

$title = $mode === 'update' ? Translate::t('Modifica permesso') : Translate::t('Nuovo permesso');

$this->setTitle($title);
$this->setParameter('pageIcon', 'pe-7s-key');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Permessi'), 'url' => '/permission'],
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
    placeholder: Translate::t('Inserisci il nome descrittivo del permesso'),
    icon: 'fa-solid fa-pen',
    validationRules: $validationRules['name'] ?? [],
    validationErrors: $errors['name'] ?? [],
    validated: $validated,
);

$form .= SelectInput::render(
    name: 'group_id',
    label: Translate::t('Gruppo'),
    value: $input->groupId,
    options: $groupOptions,
    prompt: Translate::t('Seleziona un gruppo'),
    icon: 'fa-solid fa-layer-group',
    hint: Translate::t('Il permesso viene salvato nel gruppo selezionato mantenendo nome e codice inseriti.'),
    validationRules: $validationRules['groupId'] ?? [],
    validationErrors: $errors['groupId'] ?? [],
    validated: $validated,
);

$form .= TextInput::render(
    name: 'code',
    label: Translate::t('Codice'),
    value: (string) $input->code,
    placeholder: Translate::t('Es. ACCESS'),
    icon: 'fa-solid fa-key',
    validationRules: $validationRules['code'] ?? [],
    validationErrors: $errors['code'] ?? [],
    validated: $validated,
);

$form .= TextInput::render(
    name: 'weight',
    label: Translate::t('Peso'),
    value: (string) $input->weight,
    placeholder: '1',
    icon: 'fa-solid fa-weight-hanging',
    hint: Translate::t('Peso ordinativo del permesso quando serve dare priorita nelle selezioni.'),
    validationRules: $validationRules['weight'] ?? [],
    validationErrors: $errors['weight'] ?? [],
    validated: $validated,
);

$form .= '<div class="app-form-actions mt-4">';
$form .= Field::submitButton(Translate::t('Salva'))->addButtonClass('px-4');
$form .= '</div>';

$form .= '</form>';

echo FormCard::render(
    title: $title,
    formHtml: $form,
    variant: 'info',
);

<?php

declare(strict_types=1);

use App\Mes\Task\TaskEntity;
use App\Mes\Task\TaskInput;
use App\Shared\Helpers\Translate;
use App\Shared\Widgets\BackButton;
use App\Shared\Widgets\Forms\FormCard;
use App\Shared\Widgets\Forms\FormTheme;
use App\Shared\Widgets\Inputs\DateInput;
use App\Shared\Widgets\Inputs\SelectInput;
use App\Shared\Widgets\Inputs\TextareaInput;
use App\Shared\Widgets\Inputs\TextInput;
use Yiisoft\Form\PureField\Field;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Yii\View\Renderer\Csrf;

/** @var TaskInput $input */
/** @var array $errors */
/** @var string $mode */
/** @var Csrf $csrf */
/** @var UrlGeneratorInterface $urlGenerator */
/** @var string $backUrl */
/** @var bool $validated */

FormTheme::boot();

$action = $mode === 'update'
    ? $urlGenerator->generate('task/update', ['id' => (int) $input->id])
    : $urlGenerator->generate('task/create');

$title = $mode === 'update' ? Translate::t('Modifica task') : Translate::t('Nuova task');

$this->setTitle($title);
$this->setParameter('pageIcon', 'pe-7s-note2');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Tasks'), 'url' => '/task'],
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
    name: 'title',
    label: Translate::t('Titolo'),
    value: (string) $input->title,
    placeholder: Translate::t('Inserisci un titolo chiaro'),
    icon: 'fa-solid fa-pen',
    validationRules: $validationRules['title'] ?? [],
    validationErrors: $errors['title'] ?? [],
    validated: $validated,
);

$form .= TextareaInput::render(
    name: 'description',
    label: Translate::t('Descrizione'),
    value: (string) $input->description,
    placeholder: Translate::t('Descrivi obiettivo, note operative o contesto utile'),
    icon: 'fa-solid fa-align-left',
    inputAttributes: ['rows' => 6],
    validationRules: $validationRules['description'] ?? [],
    validationErrors: $errors['description'] ?? [],
    validated: $validated,
);

$form .= '<div class="row g-3">';
$form .= '<div class="col-12 col-lg-6">';
$form .= DateInput::render(
    name: 'start_date',
    label: Translate::t('Data inizio'),
    value: $input->startDate,
    icon: 'fa-regular fa-calendar',
    validationRules: $validationRules['startDate'] ?? [],
    validationErrors: $errors['startDate'] ?? [],
    validated: $validated,
);
$form .= '</div>';
$form .= '<div class="col-12 col-lg-6">';
$form .= DateInput::render(
    name: 'end_date',
    label: Translate::t('Data fine'),
    value: $input->endDate,
    icon: 'fa-regular fa-calendar-check',
    validationRules: $validationRules['endDate'] ?? [],
    validationErrors: $errors['endDate'] ?? [],
    validated: $validated,
);
$form .= '</div>';
$form .= '</div>';

$form .= SelectInput::render(
    name: 'status',
    label: Translate::t('Stato'),
    value: (string) ($input->status ?? TaskEntity::STATUS_TODO),
    options: TaskEntity::statusOptions(),
    prompt: Translate::t('Seleziona lo stato'),
    icon: 'fa-solid fa-signal',
    validationRules: $validationRules['status'] ?? [],
    validationErrors: $errors['status'] ?? [],
    validated: $validated,
);

$form .= '<div class="app-form-actions mt-4">';
$form .= Field::submitButton(Translate::t('Salva'))->addButtonClass('px-4');
$form .= '</div>';

$form .= '</form>';

echo FormCard::render(
    title: $title,
    formHtml: $form,
    variant: 'primary',
);

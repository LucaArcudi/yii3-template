<?php

declare(strict_types=1);

namespace App\Widgets;

use App\Core\Log\LogPresenter;
use App\Helpers\Translate;
use Yiisoft\Html\Html;

final class EntityLogList
{
    public static function render(array $logs): string
    {
        if ($logs === []) {
            return Card::render(
                title: 'Log',
                body: (string) Html::div(Translate::t('Nessun log disponibile per questo record.'), ['class' => 'alert alert-light mb-0']),
                variant: 'secondary',
                icon: 'fa-solid fa-clock-rotate-left',
            );
        }

        $body = '<div class="accordion app-entity-log" id="entity-log-accordion">';

        foreach ($logs as $row) {
            $log = new LogPresenter((array) $row);
            $id = 'entity-log-' . (string) $log->id();

            $body .= '<div class="accordion-item">';
            $body .= '<h2 class="accordion-header" id="' . Html::encode($id . '-heading') . '">';
            $body .= '<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#'
                . Html::encode($id) . '" aria-expanded="false" aria-controls="' . Html::encode($id) . '">';
            $body .= '<span class="badge rounded-pill text-bg-' . Html::encode($log->actionVariant()) . ' me-2">'
                . Html::encode($log->actionLabel()) . '</span>';
            $body .= '<span class="me-3">' . Html::encode($log->createdAt()) . '</span>';
            $body .= '<span class="text-muted">' . Html::encode($log->actor()) . '</span>';
            $body .= '</button></h2>';
            $body .= '<div id="' . Html::encode($id) . '" class="accordion-collapse collapse" aria-labelledby="'
                . Html::encode($id . '-heading') . '" data-bs-parent="#entity-log-accordion">';
            $body .= '<div class="accordion-body">';
            $body .= self::meta(Translate::t('Sorgente'), $log->source());
            $body .= self::meta(Translate::t('Metodo'), $log->method());
            $body .= self::meta('URL', $log->url());
            $body .= self::meta(Translate::t('Comando console'), $log->consoleCommand());
            $body .= self::meta('IP', $log->ipAddress());
            $body .= self::codeBlock('Query', $log->query(), 'sql');
            $body .= self::codeBlock(Translate::t('Parametri SQL'), $log->params(), 'json');
            $body .= self::codeBlockIfNotEmpty(Translate::t('Parametri richiesta'), $log->requestQuery(), 'json');
            $body .= self::codeBlockIfNotEmpty(Translate::t('Body richiesta'), $log->requestBody(), 'json');
            $body .= self::meta('Entity createdAt', $log->entityCreatedAt());
            $body .= self::meta('Entity updatedAt', $log->entityUpdatedAt());
            $body .= '</div></div></div>';
        }

        $body .= '</div>';

        return Card::render(
            title: 'Log',
            body: $body,
            variant: 'secondary',
            icon: 'fa-solid fa-clock-rotate-left',
        );
    }

    private static function meta(string $label, string $value): string
    {
        return (string) Html::div(
            (string) Html::span($label, ['class' => 'fw-semibold me-2'])
            . (string) Html::span(Html::encode($value))->encode(false),
            ['class' => 'mb-2'],
        )->encode(false);
    }

    private static function codeBlock(string $label, string $value, string $language): string
    {
        return (string) Html::div(
            (string) Html::div($label, ['class' => 'fw-semibold mb-1'])
            . '<pre class="bg-light border rounded p-3 small mb-3"><code class="language-' . Html::encode($language) . '">'
            . Html::encode($value)
            . '</code></pre>',
            ['class' => 'app-entity-log__code'],
        )->encode(false);
    }

    private static function codeBlockIfNotEmpty(string $label, string $value, string $language): string
    {
        $trimmedValue = trim($value);

        if ($trimmedValue === '' || $trimmedValue === '[]' || $trimmedValue === '{}') {
            return '';
        }

        return self::codeBlock($label, $value, $language);
    }
}

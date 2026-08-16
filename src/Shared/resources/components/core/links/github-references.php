<?php

declare(strict_types=1);

use App\Shared\Dashboard\DashboardComponentDefinition;
use App\Shared\Dashboard\DashboardComponentPresenter;
use App\Shared\Helpers\Translate;
use Yiisoft\Html\Html;
use Yiisoft\User\CurrentUser;

/**
 * @var DashboardComponentPresenter $component
 * @var DashboardComponentDefinition $componentDefinition
 * @var CurrentUser $currentUser
 */

// Nei fork basta cambiare questo URL per ripuntare tutti i collegamenti.
$repoUrl = 'https://github.com/LucaArcudi/yii3-template';

$links = [
    [
        'title' => Translate::t('Repository'),
        'meta' => Translate::t('Codice sorgente, README e onboarding.'),
        'url' => $repoUrl,
        'icon' => 'fa-regular fa-share-from-square',
    ],
    [
        'title' => Translate::t('Documentazione di progetto'),
        'meta' => Translate::t('Architettura, database, DevOps e runbook operativi.'),
        'url' => $repoUrl . '/blob/main/docs/documentazione-progetto.md',
        'icon' => 'fa-solid fa-book-open',
    ],
    [
        'title' => Translate::t('Roadmap di sviluppo'),
        'meta' => Translate::t('Backlog funzionale: cose fatte e prossimi step.'),
        'url' => $repoUrl . '/blob/main/docs/roadmap-sviluppo.md',
        'icon' => 'fa-regular fa-map',
    ],
    [
        'title' => Translate::t('Roadmap infrastruttura'),
        'meta' => Translate::t('Centralizzazione log, notifiche degli alert e self-healing.'),
        'url' => $repoUrl . '/blob/main/docs/roadmap-infrastruttura.md',
        'icon' => 'fa-solid fa-server',
    ],
    [
        'title' => Translate::t('Piano di miglioramento'),
        'meta' => Translate::t('Priorità attive per template, qualità e DevOps.'),
        'url' => $repoUrl . '/blob/main/PIANO_MIGLIORAMENTO_TEMPLATE.md',
        'icon' => 'fa-solid fa-wand-magic-sparkles',
    ],
    [
        'title' => Translate::t('Changelog'),
        'meta' => Translate::t('Storia delle release e delle modifiche.'),
        'url' => $repoUrl . '/blob/main/CHANGELOG.md',
        'icon' => 'fa-regular fa-file-lines',
    ],
    [
        'title' => Translate::t('Pull request'),
        'meta' => Translate::t('Modifiche in revisione prima del merge.'),
        'url' => $repoUrl . '/pulls',
        'icon' => 'fa-regular fa-comment',
    ],
    [
        'title' => Translate::t('CI/CD (GitHub Actions)'),
        'meta' => Translate::t('Stato di build, test e deploy.'),
        'url' => $repoUrl . '/actions',
        'icon' => 'fa-solid fa-gears',
    ],
];
?>

<div class="app-dashboard-priority app-dashboard-priority--info">
    <div class="app-dashboard-priority__header">
        <div class="app-dashboard-priority__icon"><i class="fa-solid fa-link"></i></div>
        <div class="app-dashboard-priority__copy">
            <div class="app-dashboard-priority__title"><?= Translate::t('Riferimenti GitHub') ?></div>
            <div class="app-dashboard-priority__meta"><?= Translate::t('Codice, documentazione e pipeline') ?></div>
        </div>
    </div>

    <hr>

    <p class="mb-3 text-muted">
        <?= Translate::t('Guida, backlog e roadmap vivono nel repository: da qui raggiungi codice, documentazione e stato della pipeline.') ?>
    </p>

    <ul class="list-group list-group-flush app-dashboard-checklist">
        <?php foreach ($links as $link): ?>
            <li class="list-group-item">
                <span class="app-dashboard-checklist__icon"><i class="<?= Html::encode($link['icon']) ?>"></i></span>
                <span class="app-dashboard-checklist__copy">
                    <span class="app-dashboard-checklist__title">
                        <?= Html::a(
                            $link['title'],
                            $link['url'],
                            ['target' => '_blank', 'rel' => 'noopener'],
                        ) ?>
                    </span>
                    <span class="app-dashboard-checklist__meta"><?= Html::encode($link['meta']) ?></span>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

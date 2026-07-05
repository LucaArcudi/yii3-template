<?php

declare(strict_types=1);

use App\Dashboard\DashboardComponentDefinition;
use App\Dashboard\DashboardComponentPresenter;
use App\Helpers\Translate;
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
        'icon' => 'pe-7s-share',
    ],
    [
        'title' => Translate::t('Documentazione di progetto'),
        'meta' => Translate::t('Architettura, database, DevOps e runbook operativi.'),
        'url' => $repoUrl . '/blob/main/docs/documentazione-progetto.md',
        'icon' => 'pe-7s-notebook',
    ],
    [
        'title' => Translate::t('Roadmap di sviluppo'),
        'meta' => Translate::t('Backlog funzionale: cose fatte e prossimi step.'),
        'url' => $repoUrl . '/blob/main/docs/roadmap-sviluppo.md',
        'icon' => 'pe-7s-map-2',
    ],
    [
        'title' => Translate::t('Roadmap AI (Codex + Claude Code)'),
        'meta' => Translate::t('Integrazione degli agenti AI nel workflow di sviluppo.'),
        'url' => $repoUrl . '/blob/main/docs/roadmap-ai-codex-claude-code.md',
        'icon' => 'pe-7s-magic-wand',
    ],
    [
        'title' => Translate::t('Changelog'),
        'meta' => Translate::t('Storia delle release e delle modifiche.'),
        'url' => $repoUrl . '/blob/main/CHANGELOG.md',
        'icon' => 'pe-7s-note2',
    ],
    [
        'title' => Translate::t('Issue e pull request'),
        'meta' => Translate::t('Segnalazioni, proposte e revisioni in corso.'),
        'url' => $repoUrl . '/issues',
        'icon' => 'pe-7s-comment',
    ],
    [
        'title' => Translate::t('CI/CD (GitHub Actions)'),
        'meta' => Translate::t('Stato di build, test e deploy.'),
        'url' => $repoUrl . '/actions',
        'icon' => 'pe-7s-config',
    ],
];
?>

<div class="app-dashboard-priority app-dashboard-priority--info">
    <div class="app-dashboard-priority__header">
        <div class="app-dashboard-priority__icon"><i class="pe-7s-link"></i></div>
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

<?php

declare(strict_types=1);

use App\Dashboard\DashboardComponentDefinition;
use App\Dashboard\DashboardComponentPresenter;
use Yiisoft\Html\Html;
use Yiisoft\User\CurrentUser;

/**
 * @var DashboardComponentPresenter $component
 * @var DashboardComponentDefinition $componentDefinition
 * @var CurrentUser $currentUser
 */

$items = [
    [
        'title' => 'Utente super.',
        'meta' => 'Introduzione flag is_super nella tabella core_user e esclusione da liste.',
    ],
    [
        'title' => 'Notifiche',
        'meta' => 'Migliorie al sistema di notifiche.',
    ],
    [
        'title' => 'Form di creazione e modifica e filtri.',
        'meta' => 'Gestire select dipendenti per filtri statici senza autosubmit (e.g. filtri da modale, in FilterBar funzionano in autosubmit) e nei form di creazione e modifica. Implementare un esempio.',
    ],
    [
        'title' => 'DB Tweak',
        'meta' => 'Gestire chiavi esterne e indici. In particolare gestire comportamenti di cancellazione a cascata o restrizione per utenti, ruoli e permessi.',
    ],
    [
        'title' => 'Migration e seeder',
        'meta' => 'Creare migration e seeder per i dati di base e un utente admin.',
    ],
    [
        'title' => 'Traduzioni',
        'meta' => 'Lingua italiana (default), traduzione inglese disponibile.',
    ],
    [
        'title' => 'Documentazione',
        'meta' => 'Chiudere README e CHANGELOG. Creare documentazione per sviluppatori e utenti.',
    ],
];
?>

<div class="app-dashboard-priority app-dashboard-priority--warning">
    <div class="app-dashboard-priority__header">
        <div class="app-dashboard-priority__icon"><i class="pe-7s-shield"></i></div>
        <div class="app-dashboard-priority__copy">
            <div class="app-dashboard-priority__title">Backlog admin</div>
            <div class="app-dashboard-priority__meta">Admin</div>
        </div>
    </div>

    <hr>

    <p class="mb-3 text-muted">
        Attivita pre rilascio 1.0.0 da completare.
    </p>

    <ul class="list-group list-group-flush app-dashboard-checklist">
        <?php foreach ($items as $item): ?>
            <li class="list-group-item">
                <span class="app-dashboard-checklist__icon"><i class="pe-7s-angle-right-circle"></i></span>
                <span class="app-dashboard-checklist__copy">
                    <span class="app-dashboard-checklist__title"><?= Html::encode($item['title']) ?></span>
                    <span class="app-dashboard-checklist__meta"><?= Html::encode($item['meta']) ?></span>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

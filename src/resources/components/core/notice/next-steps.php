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

$items2 = [
    [
        'title' => 'Integrazione tenant per multiutenza',
        'meta' => 'Implementazione semplice tramite tenant_id.',
    ],
    [
        'title' => 'Integrazione stripe per pagamenti',
        'meta' => 'Implementare l\'integrazione con Stripe per la gestione dei pagamenti online.',
    ],
];
?>

<div class="app-dashboard-priority app-dashboard-priority--info">
    <div class="app-dashboard-priority__header">
        <div class="app-dashboard-priority__icon"><i class="pe-7s-tools"></i></div>
        <div class="app-dashboard-priority__copy">
            <div class="app-dashboard-priority__title">Prossimi step</div>
            <div class="app-dashboard-priority__meta">Sviluppo</div>
        </div>
    </div>

    <hr>

    <p class="mb-3 text-muted">
        Attivita post 1.0.0.
    </p>

        <ul class="list-group list-group-flush app-dashboard-checklist">
        <?php foreach ($items2 as $item): ?>
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

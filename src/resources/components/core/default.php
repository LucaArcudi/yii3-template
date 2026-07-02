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
?>

<div class="app-dashboard-priority app-dashboard-priority--secondary">
    <div class="app-dashboard-priority__header">
        <div class="app-dashboard-priority__icon"><i class="pe-7s-display1"></i></div>
        <div class="app-dashboard-priority__copy">
            <div class="app-dashboard-priority__title"><?= Translate::t('Componente') ?></div>
            <div class="app-dashboard-priority__meta"><?= Html::encode($component->code()) ?></div>
        </div>
    </div>

    <p class="mb-0 text-muted">
        <?= Html::encode(Translate::t('Nessuna view PHP specifica trovata per {view}.', ['view' => $component->viewName()])) ?>
    </p>
</div>

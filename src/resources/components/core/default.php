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
?>

<div class="app-dashboard-priority app-dashboard-priority--secondary">
    <div class="app-dashboard-priority__header">
        <div class="app-dashboard-priority__icon"><i class="pe-7s-display1"></i></div>
        <div class="app-dashboard-priority__copy">
            <div class="app-dashboard-priority__title">Componente</div>
            <div class="app-dashboard-priority__meta"><?= Html::encode($component->code()) ?></div>
        </div>
    </div>

    <p class="mb-0 text-muted">
        Nessuna view PHP specifica trovata per <?= Html::encode($component->viewName()) ?>.
    </p>
</div>

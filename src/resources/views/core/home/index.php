<?php

declare(strict_types=1);

use App\Data\Core\User\UserIdentity;
use App\Dashboard\DashboardComponentPresenter;
use App\Dashboard\DashboardComponentRenderer;
use App\Dashboard\DashboardComponentVisibility;
use App\Params\Core\ApplicationParams;
use Yiisoft\Html\Html;
use Yiisoft\User\CurrentUser;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var ApplicationParams $applicationParams
 * @var CurrentUser $currentUser
 * @var list<App\Dashboard\DashboardComponentDefinition> $components
 * @var DashboardComponentRenderer $componentRenderer
 */

// CONDIZIONI EXTRA UTENTE PER COMPONENTI
$identity = $currentUser->getIdentity();
// $isGianni = $identity instanceof UserIdentity && $identity->getEmail() === 'gianni@gianni.com';
$componentVisibility = [
    // 'code:admin-control' => $isGianni,
];
$components = DashboardComponentVisibility::filter($components, $componentVisibility);
unset($identity);

$this->setTitle($applicationParams->name);
$this->setParameter('pageIcon', 'pe-7s-home');
$this->setParameter('breadcrumbs', [
    ['label' => 'Dashboard'],
]);
?>

<?php if ($components === []): ?>
    <div class="main-card mb-3 card app-dashboard-section">
        <div class="card-header">
            <div>
                <div class="app-dashboard-section__eyebrow">Componenti</div>
                <h5 class="card-title mb-0">Nessun componente disponibile</h5>
            </div>
        </div>

        <div class="card-body">
            <p class="mb-0 text-muted">
                Il tuo utente non ha componenti visibili per i permessi e le regole attuali.
            </p>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($components as $component): ?>
            <?php $presenter = new DashboardComponentPresenter($component); ?>
            <div class="<?= Html::encode($presenter->width()) ?> mb-3">
                <?= $componentRenderer->render($component, $currentUser) ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

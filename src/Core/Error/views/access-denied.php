<?php

declare(strict_types=1);

use App\Shared\Helpers\Translate;
use Yiisoft\Html\Html;
use Yiisoft\User\CurrentUser;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var CurrentUser $currentUser
 */

$isGuest = $currentUser->isGuest();
$returnUrl = $isGuest ? '/login' : '/';
$returnLabel = $isGuest ? Translate::t('Vai al login') : Translate::t('Torna alla dashboard');

$this->setTitle(Translate::t('Accesso negato'));
$this->setParameter('guestHeaderSubtitle', Translate::t('Area riservata'));
$this->setParameter('guestCardSubtitle', Translate::t('La richiesta non puo essere completata con le autorizzazioni correnti.'));
$this->setParameter('pageIcon', 'pe-7s-lock');
$this->setParameter('breadcrumbs', [
    ['label' => Translate::t('Dashboard'), 'url' => '/'],
    ['label' => Translate::t('Accesso negato')],
]);
$this->setParameter(
    'pageActions',
    (string) Html::a(
        (string) Html::i('', ['class' => 'fa fa-home']),
        $returnUrl,
        ['class' => 'btn-shadow btn btn-dark', 'aria-label' => $returnLabel],
    )->encode(false),
);
?>

<div class="app-error-state">
    <div class="app-error-state__icon app-error-state__icon--warning">
        <i class="fa-solid fa-lock"></i>
    </div>
    <div class="app-error-state__code">403</div>
    <h1 class="app-error-state__title"><?= Translate::t('Accesso negato') ?></h1>
    <p class="app-error-state__message">
        <?= Translate::t('Non hai i permessi necessari per visualizzare questa pagina o completare questa operazione.') ?>
    </p>
    <?= Html::a($returnLabel, $returnUrl, ['class' => 'btn btn-primary btn-shadow'])->render() ?>
</div>

<style>
    .app-error-state {
        max-width: 560px;
        margin: 0 auto;
        padding: 24px 16px 8px;
        text-align: center;
    }

    .app-error-state__icon {
        display: inline-flex;
        width: 72px;
        height: 72px;
        align-items: center;
        justify-content: center;
        margin-bottom: 18px;
        border-radius: 50%;
        font-size: 30px;
    }

    .app-error-state__icon--warning {
        color: #9a5b00;
        background: #fff4da;
    }

    .app-error-state__code {
        margin-bottom: 8px;
        color: #6c757d;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0;
        text-transform: uppercase;
    }

    .app-error-state__title {
        margin-bottom: 10px;
        color: #1f2937;
        font-size: 28px;
        font-weight: 700;
        letter-spacing: 0;
    }

    .app-error-state__message {
        margin: 0 auto 24px;
        color: #6c757d;
        font-size: 16px;
        line-height: 1.6;
    }
</style>

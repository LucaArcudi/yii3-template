<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\User\CurrentUser;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var CurrentUser $currentUser
 * @var int|null $retryAfterSeconds
 */

$isGuest = $currentUser->isGuest();
$returnUrl = $isGuest ? '/login' : '/';
$returnLabel = $isGuest ? 'Torna al login' : 'Torna alla dashboard';
$retryAfterMessage = null;

if ($retryAfterSeconds !== null) {
    $minutes = max(1, (int) ceil($retryAfterSeconds / 60));
    $retryAfterMessage = $minutes === 1
        ? 'Riprova tra circa 1 minuto.'
        : 'Riprova tra circa ' . $minutes . ' minuti.';
}

$this->setTitle('Troppi tentativi');
$this->setParameter('guestHeaderSubtitle', 'Protezione account');
$this->setParameter('guestCardSubtitle', 'Attendi qualche minuto prima di riprovare.');
$this->setParameter('pageIcon', 'pe-7s-timer');
$this->setParameter('breadcrumbs', [
    ['label' => 'Dashboard', 'url' => '/'],
    ['label' => 'Troppi tentativi'],
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
    <div class="app-error-state__icon app-error-state__icon--rate-limit">
        <i class="fa-solid fa-hourglass-half"></i>
    </div>
    <div class="app-error-state__code">429</div>
    <h1 class="app-error-state__title">Troppi tentativi</h1>
    <p class="app-error-state__message">
        Abbiamo limitato temporaneamente questa operazione per proteggere account e sistema.
        <?php if ($retryAfterMessage !== null): ?>
            <strong><?= Html::encode($retryAfterMessage) ?></strong>
        <?php endif; ?>
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

    .app-error-state__icon--rate-limit {
        color: #155a68;
        background: #dff7fb;
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

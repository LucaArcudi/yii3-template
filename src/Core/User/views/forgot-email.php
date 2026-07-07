<?php

declare(strict_types=1);

use App\Shared\Helpers\Translate;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 */

$this->setTitle(Translate::t('Email dimenticata'));
?>
<div class="text-center mb-4">
    <h3 class="mb-1"><?= Html::encode(Translate::t('Email dimenticata')) ?></h3>
    <p class="text-muted mb-0"><?= Html::encode(Translate::t('L\'email e l\'identificativo dell\'account.')) ?></p>
</div>

<div class="alert alert-light border mb-3">
    <?= Html::encode(Translate::t('Per sicurezza non mostriamo ne suggeriamo indirizzi email partendo da nome o altri dati deboli.')) ?>
    <?= Html::encode(Translate::t('Se non ricordi quale email hai usato, contatta un amministratore del gestionale.')) ?>
</div>

<div class="d-grid gap-2">
    <?= Html::a(Translate::t('Torna al login'), '/login', ['class' => 'btn btn-primary btn-shadow'])->render() ?>
    <?= Html::a(Translate::t('Recupera password'), '/forgot-password', ['class' => 'btn btn-outline-secondary'])->render() ?>
</div>

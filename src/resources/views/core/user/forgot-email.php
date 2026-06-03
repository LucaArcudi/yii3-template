<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 */

$this->setTitle('Email dimenticata');
?>
<div class="text-center mb-4">
    <h3 class="mb-1">Email dimenticata</h3>
    <p class="text-muted mb-0">L'email e l'identificativo dell'account.</p>
</div>

<div class="alert alert-light border mb-3">
    Per sicurezza non mostriamo ne suggeriamo indirizzi email partendo da nome o altri dati deboli.
    Se non ricordi quale email hai usato, contatta un amministratore del gestionale.
</div>

<div class="d-grid gap-2">
    <?= Html::a('Torna al login', '/login', ['class' => 'btn btn-primary btn-shadow'])->render() ?>
    <?= Html::a('Recupera password', '/forgot-password', ['class' => 'btn btn-outline-secondary'])->render() ?>
</div>

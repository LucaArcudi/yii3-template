<?php
declare(strict_types=1);

use App\Helpers\Translate;
use Yiisoft\Yii\View\WebView;

/** @var WebView $this */
$this->setTitle(Translate::t('Pagina non trovata'));
$this->setParameter('pageIcon', 'pe-7s-way');
$this->setParameter('breadcrumbs', [
    ['label' => 'Dashboard', 'url' => '/'],
    ['label' => Translate::t('Pagina non trovata')],
]);
?>
<div class="not-found-wrapper">
    <h1>404 - <?= Translate::t('Pagina non trovata') ?></h1>
    <p><?= Translate::t('La pagina che cerchi non esiste o non è più disponibile.') ?></p>
    <a href="/"><?= Translate::t('Torna alla home') ?></a>
</div>

<style>
    .not-found-wrapper {
        text-align: center;
        padding: 50px;
    }
    .not-found-wrapper h1 {
        font-size: 48px;
        margin-bottom: 20px;
    }
    .not-found-wrapper p {
        font-size: 18px;
        margin-bottom: 30px;
    }
    .not-found-wrapper a {
        display: inline-block;
        padding: 10px 20px;
        background-color: #007bff;
        color: #fff;
        text-decoration: none;
        border-radius: 4px;
    }
    .not-found-wrapper a:hover {
        background-color: #0056b3;
    }
</style>

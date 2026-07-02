<?php
declare(strict_types=1);

use Yiisoft\Yii\View\WebView;

/** @var WebView $this */
$this->setTitle('404 Not Found');
$this->setParameter('pageIcon', 'pe-7s-way');
$this->setParameter('breadcrumbs', [
    ['label' => 'Dashboard', 'url' => '/'],
    ['label' => '404 Not Found'],
]);
?>
<div class="not-found-wrapper">
    <h1>404 Not Found</h1>
    <p>The page you are looking for could not be found.</p> 
    <a href="/">Go to Home</a>
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

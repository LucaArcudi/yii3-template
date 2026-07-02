<?php

declare(strict_types=1);

use App\Assets\ArchitectUi\ArchitectUiAsset;
use App\Helpers\AppLocales;
use App\Helpers\PublicAssetResolver;
use App\Helpers\Translate;
use App\Params\Core\ApplicationParams;
use App\Params\Core\LayoutParams;
use App\Widgets\FlashMessages;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Html\Html;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var string $content
 * @var ApplicationParams $applicationParams
 * @var LayoutParams $layoutParams
 * @var AssetManager $assetManager
 * @var FlashInterface $flash
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 */

$assetManager->register(ArchitectUiAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$title = $this->getTitle() ?: $applicationParams->name;
$flashHtml = FlashMessages::render($flash);
$logoUrl = PublicAssetResolver::url($layoutParams->logo);
$headerSubtitle = (string) $this->getParameter('guestHeaderSubtitle', Translate::t('Area di accesso ArchitectUI'));
$cardSubtitle = (string) $this->getParameter(
    'guestCardSubtitle',
    Translate::t('Accedi o crea un nuovo account per entrare nell\'area gestionale.'),
);
$currentLocale = $translator->getLocale();
$langLinks = [];

foreach (AppLocales::SUPPORTED as $localeCode => $localeLabel) {
    $langLinks[] = $localeCode === $currentLocale
        ? '<span class="app-guest-lang__current">' . Html::encode($localeLabel) . '</span>'
        : '<a href="/language/' . Html::encode($localeCode) . '">' . Html::encode($localeLabel) . '</a>';
}

$langSwitcherHtml = implode('<span class="mx-2">·</span>', $langLinks);
$pageModalsParameter = $this->getParameter('pageModals', null);
$pageModals = is_string($pageModalsParameter) && $pageModalsParameter !== ''
    ? $pageModalsParameter
    : '';
?>
<?php $this->beginPage() ?>
<!doctype html>
<html lang="<?= Html::encode($currentLocale) ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="<?= Html::encode($currentLocale) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($title) ?></title>
    <link rel="icon" href="/favicon.ico">
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="app-container app-theme-white body-tabs-shadow">
    <div class="app-main">
        <div class="app-main__outer w-100">
            <div class="app-main__inner">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-5 col-xl-4">
                        <div class="mb-3 text-center">
                            <?php if ($logoUrl !== null): ?>
                                <img src="<?= Html::encode($logoUrl) ?>" alt="<?= Html::encode($applicationParams->name) ?>" class="app-guest-logo">
                            <?php else: ?>
                                <h1 class="h3 mb-2"><?= Html::encode($applicationParams->name) ?></h1>
                            <?php endif; ?>
                            <p class="text-muted mb-0"><?= Html::encode($headerSubtitle) ?></p>
                        </div>

                        <div class="main-card mb-3 card">
                            <div class="card-body p-4">
                        <div class="mb-4 text-center">
                            <h2 class="card-title mb-1"><?= Html::encode($title) ?></h2>
                            <p class="text-muted mb-0"><?= Html::encode($cardSubtitle) ?></p>
                        </div>

                        <?php if ($flashHtml !== ''): ?>
                            <?= $flashHtml ?>
                        <?php endif; ?>

                        <?= $content ?>
                    </div>
                </div>

                        <div class="text-center text-muted app-guest-lang">
                            <?= $langSwitcherHtml ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($pageModals !== ''): ?>
    <?= $pageModals ?>
<?php endif; ?>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

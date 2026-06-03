<?php

declare(strict_types=1);

use App\Assets\ArchitectUi\ArchitectUiAsset;
use App\Data\Core\Notification\NotificationPolicy;
use App\Data\Core\Notification\NotificationReader;
use App\Data\Core\User\UserIdentity;
use App\Helpers\PublicAssetResolver;
use App\Navigation\NavigationProvider;
use App\Params\Core\ApplicationParams;
use App\Params\Core\LayoutParams;
use App\Widgets\Breadcrumb;
use App\Widgets\FlashMessages;
use App\Widgets\Menu;
use App\Widgets\NotificationDropdown;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Html\Html;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var string $content
 * @var ApplicationParams $applicationParams
 * @var AssetManager $assetManager
 * @var NavigationProvider $menuTreeProvider
 * @var NotificationPolicy $notificationPolicy
 * @var NotificationReader $notificationReader
 * @var LayoutParams $layoutParams
 * @var FlashInterface $flash
 * @var CurrentUser $currentUser
 * @var \Yiisoft\Yii\View\Renderer\Csrf $csrf
 */

$assetManager->register(ArchitectUiAsset::class);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$title = $this->getTitle() ?: $applicationParams->name;
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$pageIcon = (string) $this->getParameter('pageIcon', 'pe-7s-browser');
$breadcrumbsParameter = $this->getParameter('breadcrumbs', []);
$breadcrumbs = is_array($breadcrumbsParameter) ? $breadcrumbsParameter : [];

if ($breadcrumbs === [] && $currentPath !== '/') {
    $breadcrumbs = [
        ['label' => 'Dashboard', 'url' => '/'],
        ['label' => $title],
    ];
}

$breadcrumbsHtml = Breadcrumb::render($breadcrumbs);
$flashHtml = FlashMessages::render($flash);
$footerLeft = $layoutParams->footerLeft;
$footerRight = $layoutParams->footerRight;
$smallLogoUrl = PublicAssetResolver::url($layoutParams->logoSmall);
$logoAttributes = ['class' => 'logo-src'];

if ($smallLogoUrl !== null) {
    $logoAttributes['style'] = 'background-image: url("' . htmlspecialchars($smallLogoUrl, ENT_QUOTES, 'UTF-8') . '");';
}
$pageActionsParameter = $this->getParameter('pageActions', null);
$pageActions = is_string($pageActionsParameter) && $pageActionsParameter !== ''
    ? $pageActionsParameter
    : (string) Html::a(
        (string) Html::i('', ['class' => 'fa fa-home']),
        '/',
        ['class' => 'btn-shadow btn btn-dark'],
    )->encode(false);
$pageModalsParameter = $this->getParameter('pageModals', null);
$pageModals = is_string($pageModalsParameter) && $pageModalsParameter !== ''
    ? $pageModalsParameter
    : '';

// CONDIZIONI EXTRA UTENTE
$identity = $currentUser->getIdentity();
// $isArcudilu = $identity instanceof UserIdentity && $identity->getEmail() === 'gianni@gianni.com';
$menuVisibility = [
    // '/user' => $isArcudilu,
];

$currentUserName = $identity instanceof UserIdentity && $identity->getName() !== ''
    ? $identity->getName()
    : 'Utente';
$currentUserEmail = $identity instanceof UserIdentity && $identity->getEmail() !== ''
    ? $identity->getEmail()
    : '';
$initialsSource = trim($currentUserName) !== '' ? $currentUserName : $currentUserEmail;
$initialsParts = preg_split('/\s+/', trim($initialsSource)) ?: [];
$currentUserInitials = '';

foreach ($initialsParts as $part) {
    if ($part !== '') {
        $currentUserInitials .= mb_substr($part, 0, 1);
    }

    if (mb_strlen($currentUserInitials) >= 2) {
        break;
    }
}

$currentUserInitials = $currentUserInitials !== ''
    ? mb_strtoupper($currentUserInitials)
    : 'U';

$menu = $menuTreeProvider->getVisibleTree();
?>
<?php $this->beginPage() ?>
<!doctype html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="it">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?= Html::encode($applicationParams->name) ?>">
    <title><?= Html::encode($title) ?></title>
    <link rel="icon" href="/favicon.ico">
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="app-container app-theme-white body-tabs-shadow fixed-sidebar fixed-header">

    <div class="app-header header-shadow">
        <div class="app-header__logo">
            <?= Html::div('', $logoAttributes) ?>

            <div class="header__pane ms-auto">
                <div>
                    <button
                        type="button"
                        class="hamburger close-sidebar-btn hamburger--elastic"
                        data-class="closed-sidebar"
                    >
                        <span class="hamburger-box">
                            <span class="hamburger-inner"></span>
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <div class="app-header__mobile-menu">
            <div>
                <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                    <span class="hamburger-box">
                        <span class="hamburger-inner"></span>
                    </span>
                </button>
            </div>
        </div>

        <div class="app-header__menu">
            <span>
                <button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
                    <span class="btn-icon-wrapper">
                        <i class="fa fa-ellipsis-v fa-w-6"></i>
                    </span>
                </button>
            </span>
        </div>

        <div class="app-header__content">
            <div class="app-header-left">
                <div class="page-title-heading mb-0">
                    <div>
                        <?= Html::encode($applicationParams->name) ?>
                    </div>
                </div>
            </div>

            <div class="app-header-right">
                <?php if ($notificationPolicy->canUse()): ?>
                    <div class="header-btn-lg pe-0 me-2">
                        <?= NotificationDropdown::render($notificationReader, $csrf, $notificationPolicy->canAccess()) ?>
                    </div>
                <?php endif; ?>

                <div class="header-btn-lg pe-0">
                    <div class="widget-content p-0">
                        <div class="widget-content-wrapper">
                            <div class="widget-content-left">
                                <div class="btn-group app-user-menu">
                                    <button
                                        type="button"
                                        class="p-0 btn app-user-menu__toggle"
                                        data-bs-toggle="dropdown"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                    >
                                        <span class="rounded-circle app-user-menu__avatar">
                                            <?= Html::encode($currentUserInitials) ?>
                                        </span>
                                        <span class="app-user-menu__identity d-none d-md-flex">
                                            <span class="widget-heading app-user-menu__name"><?= Html::encode($currentUserName) ?></span>
                                            <?php if ($currentUserEmail !== ''): ?>
                                                <span class="widget-subheading app-user-menu__email"><?= Html::encode($currentUserEmail) ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <i class="fa fa-angle-down ms-2 opacity-8"></i>
                                    </button>

                                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-lg app-user-menu__dropdown">
                                        <div class="dropdown-menu-header">
                                            <div class="dropdown-menu-header-inner bg-mean-fruit">
                                                <div class="menu-header-content text-start">
                                                    <div class="app-user-menu__header-avatar">
                                                        <?= Html::encode($currentUserInitials) ?>
                                                    </div>
                                                    <div>
                                                        <div class="menu-header-title"><?= Html::encode($currentUserName) ?></div>
                                                        <?php if ($currentUserEmail !== ''): ?>
                                                            <div class="menu-header-subtitle"><?= Html::encode($currentUserEmail) ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="nav flex-column">
                                            <a href="/profile" class="dropdown-item app-user-menu__item">
                                                <i class="fa-regular fa-user me-2"></i>
                                                Gestione profilo
                                            </a>
                                            <a href="/change-password" class="dropdown-item app-user-menu__item">
                                                <i class="fa-solid fa-key me-2"></i>
                                                Cambio password
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <form method="post" action="/logout" class="m-0">
                                                <?= $csrf->hiddenInput() ?>
                                                <button type="submit" class="dropdown-item app-user-menu__item text-danger">
                                                    <i class="fa-solid fa-right-from-bracket me-2"></i>
                                                    Logout
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="app-main">

        <div class="app-sidebar sidebar-shadow">
            <div class="app-header__logo">
                <?= Html::div('', $logoAttributes) ?>

                <div class="header__pane ms-auto">
                    <div>
                        <button
                            type="button"
                            class="hamburger close-sidebar-btn hamburger--elastic"
                            data-class="closed-sidebar"
                        >
                            <span class="hamburger-box">
                                <span class="hamburger-inner"></span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="app-header__mobile-menu">
                <div>
                    <button type="button" class="hamburger hamburger--elastic mobile-toggle-nav">
                        <span class="hamburger-box">
                            <span class="hamburger-inner"></span>
                        </span>
                    </button>
                </div>
            </div>

            <div class="app-header__menu">
                <span>
                    <button type="button" class="btn-icon btn-icon-only btn btn-primary btn-sm mobile-toggle-header-nav">
                        <span class="btn-icon-wrapper">
                            <i class="fa fa-ellipsis-v fa-w-6"></i>
                        </span>
                    </button>
                </span>
            </div>

            <div class="scrollbar-sidebar">
                <div class="app-sidebar__inner">
                    <ul class="vertical-nav-menu">
                        <?= Menu::render($menu, $currentPath, $menuVisibility) ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="app-main__outer">
            <div class="app-main__inner">

                <div class="app-page-title">
                    <div class="page-title-wrapper">
                        <div class="page-title-heading">
                            <div class="page-title-icon">
                                <i class="<?= Html::encode($pageIcon) ?> icon-gradient bg-mean-fruit"></i>
                            </div>

                            <div class="app-page-title__copy">
                                <div class="app-page-title__title"><?= Html::encode($title) ?></div>

                                <?php if ($breadcrumbsHtml !== ''): ?>
                                    <div class="page-title-subheading">
                                        <?= $breadcrumbsHtml ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="page-title-actions">
                            <?= $pageActions ?>
                        </div>
                    </div>
                </div>

                <?php if ($flashHtml !== ''): ?>
                    <?= $flashHtml ?>
                <?php endif; ?>

                <?= $content ?>

            </div>

            <div class="app-wrapper-footer">
                <div class="app-footer">
                    <div class="app-footer__inner">
                        <div class="app-footer-left">
                            <ul class="nav">
                                <li class="nav-item">
                                    <span class="nav-link"><?= Html::encode($footerLeft) ?></span>
                                </li>
                            </ul>
                        </div>

                        <div class="app-footer-right">
                            <ul class="nav">
                                <li class="nav-item">
                                    <span class="nav-link"><?= Html::encode($footerRight) ?></span>
                                </li>
                            </ul>
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

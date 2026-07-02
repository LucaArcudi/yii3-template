<?php

declare(strict_types=1);

use App\Data\Core\Notification\NotificationPolicy;
use App\Data\Core\Notification\NotificationReader;
use App\Helpers\AppLocales;
use App\Navigation\NavigationProvider;
use App\Params\Core\ApplicationParams;
use App\Params\Core\LayoutParams;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Definitions\Reference;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Form\Theme\ThemePath;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\User\CurrentUser;
use Yiisoft\Yii\View\Renderer\CsrfViewInjection;

$application = require __DIR__ . '/application.php';
$env = static function (string $name, string $default, bool $allowEmpty = false): string {
    $value = getenv($name);

    if ($value === false) {
        $value = $_ENV[$name] ?? null;
    }

    return $value === null || (!$allowEmpty && $value === '') ? $default : (string) $value;
};
$envBool = static function (string $name, bool $default) use ($env): bool {
    $value = $env($name, $default ? 'true' : 'false', allowEmpty: true);

    if ($value === '') {
        return $default;
    }

    $resolved = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    return $resolved ?? $default;
};
$envInt = static function (string $name, int $default) use ($env): int {
    $value = $env($name, (string) $default, allowEmpty: true);

    return $value === '' ? $default : (int) $value;
};
$rootPath = dirname(__DIR__, 2);
$sessionSavePath = $env('SESSION_SAVE_PATH', $rootPath . '/runtime/sessions');

if (!is_dir($sessionSavePath)) {
    mkdir($sessionSavePath, 0775, true);
}

$defaultCookieSecretKey = 'change-this-cookie-secret-key-before-prod-32-bytes';
$cookieSecretKey = $env('AUTH_COOKIE_SECRET_KEY', $defaultCookieSecretKey);

if ($env('APP_ENV', 'dev') === 'prod' && $cookieSecretKey === $defaultCookieSecretKey) {
    throw new RuntimeException(
        'AUTH_COOKIE_SECRET_KEY must be set to a unique secret value in production.',
    );
}

return [
    'application' => $application,

    'auth' => [
        'passwordMaxAgeDays' => $envInt('AUTH_PASSWORD_MAX_AGE_DAYS', 90),
        'passwordResetTokenTtlMinutes' => $envInt('AUTH_PASSWORD_RESET_TOKEN_TTL_MINUTES', 60),
        'rateLimitWindowSeconds' => $envInt('AUTH_RATE_LIMIT_WINDOW_SECONDS', 300),
        'rateLimitBlockSeconds' => $envInt('AUTH_RATE_LIMIT_BLOCK_SECONDS', 900),
        'loginMaxAttempts' => $envInt('AUTH_LOGIN_MAX_ATTEMPTS', 5),
        'registrationMaxAttempts' => $envInt('AUTH_REGISTRATION_MAX_ATTEMPTS', 3),
        'passwordResetMaxAttempts' => $envInt('AUTH_PASSWORD_RESET_MAX_ATTEMPTS', 3),
        'passwordChangeMaxAttempts' => $envInt('AUTH_PASSWORD_CHANGE_MAX_ATTEMPTS', 5),
        'defaultRegistrationRoleCode' => $env('AUTH_DEFAULT_REGISTRATION_ROLE_CODE', 'UTENTE_ESTERNO'),
    ],

    'cookies' => [
        'secretKey' => $cookieSecretKey,
    ],

    'entityLog' => [
        'enabled' => $envBool('ENTITY_LOG_ENABLED', true),
        'webEnabled' => $envBool('ENTITY_LOG_WEB_ENABLED', true),
        'consoleEnabled' => $envBool('ENTITY_LOG_CONSOLE_ENABLED', false),
        'systemEnabled' => $envBool('ENTITY_LOG_SYSTEM_ENABLED', true),
    ],

    'mail' => [
        'fromEmail' => $env('MAIL_FROM_EMAIL', 'no-reply@example.test'),
        'fromName' => $env('MAIL_FROM_NAME', (string) $application['name']),
        'transport' => $env('MAIL_TRANSPORT', 'file'),
        'filePath' => $env('MAIL_FILE_PATH', '@runtime/emails'),
        'smtpHost' => $env('MAIL_SMTP_HOST', 'smtp.example.test'),
        'smtpPort' => (int) $env('MAIL_SMTP_PORT', '587'),
        'smtpUsername' => $env('MAIL_SMTP_USERNAME', 'username@example.test', allowEmpty: true),
        'smtpPassword' => $env('MAIL_SMTP_PASSWORD', 'change-me', allowEmpty: true),
        'smtpEncryption' => strtolower($env('MAIL_SMTP_ENCRYPTION', 'tls')),
        'smtpTimeout' => (int) $env('MAIL_SMTP_TIMEOUT', '15'),
    ],

    'layout' => [
        'logo' => $env('APP_LOGO', 'images/logo.png', allowEmpty: true),
        'logoSmall' => $env('APP_LOGO_SMALL', 'images/logo_small.png', allowEmpty: true),
        'footerLeft' => $env('APP_FOOTER_LEFT', (string) $application['name'], allowEmpty: true),
        'footerRight' => $env('APP_FOOTER_RIGHT', 'Yii3 + ArchitectUI', allowEmpty: true),
    ],

    'yiisoft/aliases' => [
        'aliases' => require __DIR__ . '/aliases.php',
    ],

    // fallbackLocale null: a ID mancante il translator restituisce l'ID stesso,
    // che è già il testo sorgente (italiano per 'app', inglese per 'yii-validator').
    'yiisoft/translator' => [
        'locale' => AppLocales::DEFAULT,
        'fallbackLocale' => null,
        'defaultCategory' => 'app',
    ],

    'yiisoft/form' => [
        'themes' => [
            'default' => require ThemePath::BOOTSTRAP5_VERTICAL,
        ],
        'defaultTheme' => 'default',
    ],

    'yiisoft/session' => [
        'session' => [
            'options' => [
                'save_path' => $sessionSavePath,
                'cookie_secure' => $envBool('SESSION_COOKIE_SECURE', $env('APP_ENV', 'dev') === 'prod') ? 1 : 0,
                'cookie_httponly' => 1,
                'cookie_samesite' => $env('SESSION_COOKIE_SAMESITE', 'Lax'),
                'use_only_cookies' => 1,
                'use_strict_mode' => 1,
            ],
        ],
    ],

    'yiisoft/view' => [
        'basePath' => null,
        'parameters' => [
            'urlGenerator' => Reference::to(UrlGeneratorInterface::class),
            'currentRoute' => Reference::to(CurrentRoute::class),
            'menuTreeProvider' => Reference::to(NavigationProvider::class),
            'notificationPolicy' => Reference::to(NotificationPolicy::class),
            'notificationReader' => Reference::to(NotificationReader::class),
            'assetManager' => Reference::to(AssetManager::class),
            'applicationParams' => Reference::to(ApplicationParams::class),
            'layoutParams' => Reference::to(LayoutParams::class),
            'aliases' => Reference::to(Aliases::class),
            'flash' => Reference::to(FlashInterface::class),
            'currentUser' => Reference::to(CurrentUser::class),
            'translator' => Reference::to(TranslatorInterface::class),
        ],
    ],

    'yiisoft/yii-view-renderer' => [
        'viewPath' => '@views',
        'layout' => '@resources/layouts/main',
        'injections' => [
            Reference::to(CsrfViewInjection::class),
        ],
    ],
];

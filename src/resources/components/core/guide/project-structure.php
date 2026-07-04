<?php

declare(strict_types=1);

use App\Dashboard\DashboardComponentDefinition;
use App\Dashboard\DashboardComponentPresenter;
use App\Helpers\Translate;
use Yiisoft\Html\Html;
use Yiisoft\User\CurrentUser;

/**
 * @var DashboardComponentPresenter $component
 * @var DashboardComponentDefinition $componentDefinition
 * @var CurrentUser $currentUser
 */

$sections = [
    [
        'path' => 'config/',
        'title' => Translate::t('Configurazione applicativa'),
        'description' => Translate::t('Alias, container DI, route, parametri e configurazioni separate per web, console e ambienti.'),
        'examples' => ['config/common/di/db.php', 'config/common/routes.php', 'config/environments/dev/params.php'],
    ],
    [
        'path' => 'src/Data/',
        'title' => Translate::t('Modello dati e regole dominio'),
        'description' => Translate::t('Entita, input, filtri, presenter, policy, repository, reader e scope dei moduli applicativi.'),
        'examples' => ['src/Data/Core/User/UserEntity.php', 'src/Data/Core/Role/RoleRepository.php', 'src/Data/Mes/Task/TaskPolicy.php'],
    ],
    [
        'path' => 'src/Handlers/',
        'title' => Translate::t('Ingresso delle richieste'),
        'description' => Translate::t('Action web/API e middleware: qui le richieste HTTP vengono validate, orchestrate e passate ai servizi.'),
        'examples' => ['src/Handlers/Web/Core/Home/IndexAction.php', 'src/Handlers/Middleware/Core/RedirectGuestToLoginMiddleware.php'],
    ],
    [
        'path' => 'src/Services/',
        'title' => Translate::t('Servizi applicativi'),
        'description' => Translate::t('Logica riusabile tra handler e moduli, come autorizzazione, autenticazione, mail e supporto alle viste.'),
        'examples' => ['src/Services/Core/AuthorizationService.php', 'src/Services/Core/Mail/Mailer.php'],
    ],
    [
        'path' => 'src/Dashboard/',
        'title' => Translate::t('Componenti dashboard'),
        'description' => Translate::t('Definizione, visibilita e rendering dei blocchi mostrati nella home autenticata.'),
        'examples' => ['src/Dashboard/DashboardComponentProvider.php', 'src/resources/components/core/guide/project-structure.php'],
    ],
    [
        'path' => 'src/resources/',
        'title' => Translate::t('View, layout e template'),
        'description' => Translate::t('Layout HTML, view dei moduli, componenti dashboard, template email e risorse ArchitectUI.'),
        'examples' => ['src/resources/layouts/main.php', 'src/resources/views/core/user', 'src/resources/components/core'],
    ],
    [
        'path' => 'src/Widgets/',
        'title' => Translate::t('Widget UI riusabili'),
        'description' => Translate::t('Componenti PHP per form, input, CRUD, liste, badge, menu, modali e viste dettaglio.'),
        'examples' => ['src/Widgets/Card.php', 'src/Widgets/Crud/CrudActions.php', 'src/Widgets/Forms'],
    ],
    [
        'path' => 'src/Assets/ e assets/',
        'title' => Translate::t('Asset e bundle frontend'),
        'description' => Translate::t('Bundle PHP che pubblicano gli asset e file statici sorgente come CSS custom del progetto.'),
        'examples' => ['src/Assets/ArchitectUi/ArchitectUiAsset.php', 'assets/main/site.css'],
    ],
    [
        'path' => 'database/',
        'title' => Translate::t('Database di rilascio'),
        'description' => Translate::t('Script SQL idempotenti per migrazioni e seed dei dati iniziali o di release.'),
        'examples' => ['database/migrations/release_1_0_0.sql', 'database/seeders/release_1_0_0.sql'],
    ],
    [
        'path' => 'public/',
        'title' => Translate::t('Document root'),
        'description' => Translate::t('Entry point web, favicon, robots e asset pubblicati dal framework.'),
        'examples' => ['public/index.php', 'public/assets', 'public/robots.txt'],
    ],
    [
        'path' => 'tests/',
        'title' => Translate::t('Test automatici'),
        'description' => Translate::t('Suite Codeception per controllare unita, flussi funzionali e regressioni applicative.'),
        'examples' => ['tests/Unit', 'tests/Functional', 'codeception.yml'],
    ],
    [
        'path' => 'runtime/ e vendor/',
        'title' => Translate::t('File generati e dipendenze'),
        'description' => Translate::t('Cache/log temporanei e librerie installate da Composer. Di norma non contengono codice applicativo da modificare.'),
        'examples' => ['runtime', 'vendor', 'composer.json'],
    ],
];
?>

<div class="app-dashboard-priority app-dashboard-priority--success">
    <div class="app-dashboard-priority__header">
        <div class="app-dashboard-priority__icon"><i class="pe-7s-map-2"></i></div>
        <div class="app-dashboard-priority__copy">
            <div class="app-dashboard-priority__title"><?= Translate::t('Guida progetto') ?></div>
            <div class="app-dashboard-priority__meta"><?= Translate::t('Struttura principale delle cartelle') ?></div>
        </div>
    </div>

    <hr>

    <p class="mb-3 text-muted">
        <?= Translate::t('Panoramica rapida delle aree del progetto e dei file da consultare quando si deve orientare una modifica.') ?>
    </p>

    <div class="app-dashboard-architecture">
        <?php foreach ($sections as $section): ?>
            <div class="app-dashboard-architecture__item">
                <div class="app-dashboard-architecture__path"><?= Html::encode($section['path']) ?></div>
                <div class="app-dashboard-checklist__title mt-2"><?= Html::encode($section['title']) ?></div>
                <div class="app-dashboard-architecture__description"><?= Html::encode($section['description']) ?></div>

                <ul class="mb-0 mt-2 pl-3 text-muted">
                    <?php foreach ($section['examples'] as $example): ?>
                        <li><code><?= Html::encode($example) ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php

declare(strict_types=1);

use App\Helpers\Translate;

/**
 * @var string $loginUrl
 * @var string $name
 */

$encode = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<h1 style="color:#343a40;font-size:24px;line-height:1.25;margin:0 0 16px;">
    <?= $encode(Translate::t('Ciao {name},', ['name' => $name])) ?>
</h1>

<p style="color:#5c6773;font-size:15px;line-height:1.7;margin:0 0 18px;">
    <?= $encode(Translate::t('il tuo account è stato creato correttamente. Puoi accedere all\'area gestionale dal pulsante qui sotto.')) ?>
</p>

<p style="margin:0 0 24px;">
    <a href="<?= $encode($loginUrl) ?>" style="background:#3f6ad8;border-radius:8px;color:#ffffff;display:inline-block;font-size:14px;font-weight:700;padding:12px 18px;text-decoration:none;">
        <?= $encode(Translate::t('Vai al login')) ?>
    </a>
</p>

<p style="color:#7b8794;font-size:13px;line-height:1.6;margin:0;">
    <?= $encode(Translate::t('Se il pulsante non funziona, copia questo indirizzo nel browser:')) ?><br>
    <a href="<?= $encode($loginUrl) ?>" style="color:#3f6ad8;word-break:break-all;"><?= $encode($loginUrl) ?></a>
</p>

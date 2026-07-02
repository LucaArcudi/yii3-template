<?php

declare(strict_types=1);

use App\Helpers\Translate;

/**
 * @var int $expiresMinutes
 * @var string $name
 * @var string $resetUrl
 */

$encode = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<h1 style="color:#343a40;font-size:24px;line-height:1.25;margin:0 0 16px;">
    <?= $encode(Translate::t('Ciao {name},', ['name' => $name])) ?>
</h1>

<p style="color:#5c6773;font-size:15px;line-height:1.7;margin:0 0 18px;">
    <?= $encode(Translate::t('Abbiamo ricevuto una richiesta di cambio password. Usa il pulsante qui sotto per impostarne una nuova.')) ?>
</p>

<p style="margin:0 0 24px;">
    <a href="<?= $encode($resetUrl) ?>" style="background:#3f6ad8;border-radius:8px;color:#ffffff;display:inline-block;font-size:14px;font-weight:700;padding:12px 18px;text-decoration:none;">
        <?= $encode(Translate::t('Cambia password')) ?>
    </a>
</p>

<p style="color:#5c6773;font-size:14px;line-height:1.7;margin:0 0 16px;">
    <?= $encode(Translate::t('Il link scade tra {minutes} minuti. Se non hai richiesto tu il cambio password, ignora questa email.', ['minutes' => (int) $expiresMinutes])) ?>
</p>

<p style="color:#7b8794;font-size:13px;line-height:1.6;margin:0;">
    <?= $encode(Translate::t('Se il pulsante non funziona, copia questo indirizzo nel browser:')) ?><br>
    <a href="<?= $encode($resetUrl) ?>" style="color:#3f6ad8;word-break:break-all;"><?= $encode($resetUrl) ?></a>
</p>

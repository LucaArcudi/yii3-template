<?php

declare(strict_types=1);

use App\Helpers\Translate;

/**
 * @var string $applicationName
 * @var string $content
 * @var string|null $preheader
 * @var string|null $subject
 */

$encode = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$preheaderText = trim((string) ($preheader ?? ''));
$title = trim((string) ($subject ?? $applicationName));
?>
<!doctype html>
<html lang="<?= $encode(Translate::locale()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $encode($title) ?></title>
</head>
<body style="margin:0;padding:0;background:#f5f7fb;color:#343a40;font-family:Arial,Helvetica,sans-serif;">
<?php if ($preheaderText !== ''): ?>
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;">
        <?= $encode($preheaderText) ?>
    </div>
<?php endif; ?>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7fb;margin:0;padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border:1px solid #e8edf3;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="background:#3f6ad8;color:#ffffff;padding:22px 28px;">
                        <div style="font-size:18px;font-weight:700;line-height:1.3;">
                            <?= $encode($applicationName) ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <?= $content ?>
                    </td>
                </tr>
                <tr>
                    <td style="border-top:1px solid #edf1f5;color:#7b8794;font-size:12px;line-height:1.6;padding:18px 28px;">
                        <?= $encode(Translate::t('Email automatica inviata da {app}.', ['app' => $applicationName])) ?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

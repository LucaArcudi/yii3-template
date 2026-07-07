<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Params\ApplicationParams;
use App\Shared\Services\Mail\EmailRenderer;
use Codeception\Test\Unit;
use Yiisoft\Aliases\Aliases;

final class EmailRendererTest extends Unit
{
    public function testWelcomeEmailUsesSharedLayout(): void
    {
        $renderer = new EmailRenderer($this->aliases(), new ApplicationParams(name: 'Demo App'));

        $html = $renderer->render('core/user/welcome', [
            'subject' => 'Account creato',
            'preheader' => 'Il tuo account e pronto.',
            'name' => 'Luca',
            'loginUrl' => 'https://example.test/login',
        ]);

        self::assertStringContainsString('<!doctype html>', $html);
        self::assertStringContainsString('Demo App', $html);
        self::assertStringContainsString('Ciao Luca', $html);
        self::assertStringContainsString('https://example.test/login', $html);
        self::assertStringContainsString('Email automatica inviata da Demo App.', $html);
    }

    private function aliases(): Aliases
    {
        $root = dirname(__DIR__, 2);

        return new Aliases([
            '@root' => $root,
            '@src' => $root . '/src',
            '@resources' => $root . '/src/Shared/resources',
        ]);
    }
}

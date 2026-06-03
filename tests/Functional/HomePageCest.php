<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\FunctionalTester;
use HttpSoft\Message\ServerRequest;

use function PHPUnit\Framework\assertSame;

final class HomePageCest
{
    public function guestIsRedirectedToLogin(FunctionalTester $tester): void
    {
        $response = $tester->sendRequest(
            new ServerRequest(uri: '/'),
        );

        assertSame(302, $response->getStatusCode());
        assertSame('login', $response->getHeaderLine('Location'));
    }
}

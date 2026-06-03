<?php

declare(strict_types=1);

namespace App\Tests\Web;

use App\Tests\Support\WebTester;

final class HomePageCest
{
    public function base(WebTester $I): void
    {
        $I->wantTo('guest users are redirected from the home page to login.');
        $I->amOnPage('/');
        $I->expectTo('be redirected to the login page.');
        $I->seeInCurrentUrl('/login');
        $I->see('Email');
    }
}

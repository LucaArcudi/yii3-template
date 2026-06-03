<?php

declare(strict_types=1);

namespace App\Tests\Web;

use App\Tests\Support\WebTester;

final class NotFoundHandlerCest
{
    public function nonExistentPage(WebTester $I): void
    {
        $I->wantTo('see 404 page.');
        $I->amOnPage('/non-existent-page');
        $I->canSeeResponseCodeIs(404);
        $I->see('404');
        $I->see('The page you are looking for could not be found.');
    }

    public function returnHome(WebTester $I): void
    {
        $I->wantTo('check the not found page keeps a home link.');
        $I->amOnPage('/non-existent-page');
        $I->canSeeResponseCodeIs(404);
        $I->click('Go to Home');
        $I->expectTo('be redirected to login because dashboard is protected.');
        $I->seeInCurrentUrl('/login');
    }
}

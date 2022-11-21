<?php

namespace Tests\Functional;

use Tests\Support\FunctionalTester;

class ErrorCest
{
    public function notFound(FunctionalTester $I)
    {
        $I->amOnPage('/_error/404');
        $I->see('Resource not found');
    }

    public function accessForbbiden(FunctionalTester $I)
    {
        $I->amOnPage('/_error/403');
        $I->see('Access forbidden');
    }

    public function generic(FunctionalTester $I)
    {
        $I->amOnPage('/_error/500');
        $I->see('An error occurred');
    }
}

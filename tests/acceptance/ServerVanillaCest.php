<?php


class ServerVanillaCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // tests
    public function viewPage(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('Portal overview');
    }
}

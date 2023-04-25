<?php
namespace Tests\Support\Page\Acceptance;

use Tests\Support\AcceptanceTester;

class Login
{
    // include url of current page
    public static $URL = '/';

    public static $usernameField = 'input[name="user_id"]';
    public static $passwordField = 'input[name="password"]';
    public static $loginButton = 'form[name="login"] input[type="submit"]';

    public function __construct(protected AcceptanceTester $tester)
    {
    }

    public function login($name, $password)
    {
        $I = $this->tester;

        $I->amOnPage(self::$URL);
        $I->fillField(self::$usernameField, $name);
        $I->fillField(self::$passwordField, $password);
        $I->click(self::$loginButton);

        return $this;
    }
}

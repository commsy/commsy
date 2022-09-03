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

    /**
     * @var \AcceptanceTester
     */
    protected $tester;

    public function __construct(AcceptanceTester $I)
    {
        $this->tester = $I;
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

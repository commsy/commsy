<?php
namespace App\Tests\Page\Functional;

use App\Entity\Portal;
use App\Tests\FunctionalTester;

class Registration
{
    public string $firstnameField = '#sign_up_form_firstname';
    public string $lastnameField = '#sign_up_form_lastname';
    public string $usernameField = '#sign_up_form_username';
    public string $firstEmailField = '#sign_up_form_email_first';
    public string $secondEmailField = '#sign_up_form_email_second';
    public string $firstPasswordField = '#sign_up_form_plainPassword_first';
    public string $secondPasswordField = '#sign_up_form_plainPassword_second';
    public string $submitButton = '#sign_up_form_submit';

    /**
     * @var FunctionalTester ;
     */
    protected FunctionalTester $functionalTester;

    public function __construct(FunctionalTester $I)
    {
        $this->functionalTester = $I;
    }

    public function register(
        Portal $portal,
        string $firstname,
        string $lastname,
        string $username,
        string $email,
        string $password
    ) {
        $I = $this->functionalTester;

        $I->amOnRoute('app_account_signup', [
            'id' => $portal->getId(),
        ]);

        $I->fillField($this->firstnameField, $firstname);
        $I->fillField($this->lastnameField, $lastname);
        $I->fillField($this->usernameField, $username);
        $I->fillField($this->firstEmailField, $email);
        $I->fillField($this->secondEmailField, $email);
        $I->fillField($this->firstPasswordField, $password);
        $I->fillField($this->secondPasswordField, $password);
        $I->click($this->submitButton);
    }
}

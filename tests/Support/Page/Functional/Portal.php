<?php
namespace Tests\Support\Page\Functional;

use Tests\Support\FunctionalTester;

class Portal
{
    public string $titleField = '#portal_general_title';
    public string $germanDescriptionField = '#portal_general_descriptionGerman';
    public string $englishDescriptionField = '#portal_general_descriptionEnglish';

    public string $submitButton = '#portal_general_save';

    /**
     * @var FunctionalTester ;
     */
    protected FunctionalTester $functionalTester;

    public function __construct(FunctionalTester $I)
    {
        $this->functionalTester = $I;
    }

    public function create(string $title, string $germanDesc = '', string $englishDesc = ''): void
    {
        $I = $this->functionalTester;

        $I->amOnRoute('app_server_createportal');

        $I->fillField($this->titleField, $title);
        $I->fillField($this->germanDescriptionField, $germanDesc);
        $I->fillField($this->englishDescriptionField, $englishDesc);
        $I->click($this->submitButton);
    }
}

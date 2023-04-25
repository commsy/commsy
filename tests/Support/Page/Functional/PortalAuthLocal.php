<?php
namespace Tests\Support\Page\Functional;

use Tests\Support\FunctionalTester;

class PortalAuthLocal
{
    public string $enabledField = '#auth_local_enabled';
    public string $titleField = '#auth_local_title';
    public string $defaultField = '#auth_local_default';
    public string $mailRegexField = '#auth_local_mailRegex';
    public string $addAccountField = '#auth_local_addAccount input[type=radio]';

    public string $submitButton = '#auth_local_save';

    public function __construct(
        protected FunctionalTester $functionalTester
    ) {
    }

    public function configure(
        int $portalId,
        bool $enabled,
        string $title = 'Lokal',
        bool $default = true,
        string $mailRegex = '',
        /* yes / no / invitation */ string $addAccount = 'yes'
    ): void
    {
        $I = $this->functionalTester;

        $I->amOnRoute('app_portalsettings_authlocal', [
            'portalId' => $portalId,
        ]);

        $enabled ? $I->checkOption($this->enabledField) : $I->uncheckOption($this->enabledField);
        $I->fillField($this->titleField, $title);
        $default ? $I->checkOption($this->defaultField) : $I->uncheckOption($this->defaultField);
        $I->fillField($this->mailRegexField, $mailRegex);
        $I->selectOption($this->addAccountField, $addAccount);
        $I->click($this->submitButton);
    }
}

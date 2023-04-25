<?php
namespace Tests\Support\Page\Functional;

use Tests\Support\FunctionalTester;

class PortalAuthShibboleth
{
    public string $enabledField = '#auth_shibboleth_enabled';
    public string $titleField = '#auth_shibboleth_title';
    public string $loginUrlField = '#auth_shibboleth_loginUrl';
    public string $mappingUsernameField = '#auth_shibboleth_mappingUsername';
    public string $mappingFirstnameField = '#auth_shibboleth_mappingFirstname';
    public string $mappingLastnameField = '#auth_shibboleth_mappingLastname';
    public string $mappingEmailField = '#auth_shibboleth_mappingEmail';

    public string $submitButton = '#auth_shibboleth_save';

    public function __construct(
        protected FunctionalTester $functionalTester
    ) {
    }

    public function configure(
        int $portalId,
        bool $enabled,
        string $title = 'Shibboleth',
        string $loginUrl = 'https://example.com',
        string $mappingUsername = 'eppn',
        string $mappingFirstname = 'givenName',
        string $mappingLastname = 'sn',
        string $mappingEmail = 'mail'
    ): void
    {
        $I = $this->functionalTester;

        $I->amOnRoute('app_portalsettings_authshibboleth', [
            'portalId' => $portalId,
        ]);

        $enabled ? $I->checkOption($this->enabledField) : $I->uncheckOption($this->enabledField);

        $I->fillField($this->titleField, $title);
        $I->fillField($this->loginUrlField, $loginUrl);
        $I->fillField($this->mappingUsernameField, $mappingUsername);
        $I->fillField($this->mappingFirstnameField, $mappingFirstname);
        $I->fillField($this->mappingLastnameField, $mappingLastname);
        $I->fillField($this->mappingEmailField, $mappingEmail);
        $I->click($this->submitButton);
    }
}

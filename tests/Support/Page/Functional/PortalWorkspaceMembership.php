<?php
namespace Tests\Support\Page\Functional;

use Tests\Support\FunctionalTester;

class PortalWorkspaceMembership
{
    public string $enabledField = '#auth_workspace_membership_authMembershipEnabled';
    public string $identifierField = '#auth_workspace_membership_authMembershipIdentifier';

    public string $submitButton = '#auth_workspace_membership_save';

    /**
     * @var FunctionalTester ;
     */
    protected FunctionalTester $functionalTester;

    public function __construct(FunctionalTester $I)
    {
        $this->functionalTester = $I;
    }

    public function configure(int $portalId, bool $enabled, string $identifier): void
    {
        $I = $this->functionalTester;

        $I->amOnRoute('app_portalsettings_authworkspacemembership', [
            'portalId' => $portalId,
        ]);

        $enabled ? $I->checkOption($this->enabledField) : $I->uncheckOption($this->enabledField);

        $I->fillField($this->identifierField, $identifier);
        $I->click($this->submitButton);
    }
}

<?php


class UserCest
{
    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function guestUserEmailTest(UnitTester $I)
    {
        /** @var \cs_environment $legacyEnvironment */
        $legacyEnvironment = $I->grabService('commsy_legacy.environment')->getEnvironment();

        /** @var \cs_user_item $rootUser */
        $rootUser = $legacyEnvironment->getRootUserItem();

        $portalItem = $I->createPortal('Portal', $rootUser);
        $legacyEnvironment->setCurrentContextID($portalItem->getItemId());

        $portalUser = $I->createPortalUser('portalUser', 'Vorname', 'Nachname', 'user@commsy.net', 'passwort', $portalItem);
        $legacyEnvironment->setCurrentUser($portalUser);

        $communityRoom = $I->createCommunityRoom('Room', $portalUser, $portalItem);
        $communityRoom->setOpenForGuests();

        $legacyEnvironment->setCurrentContextID($communityRoom->getItemId());

        $userManager = $legacyEnvironment->getUserManager();
        $guest = $userManager->getNewItem();
        $guest->setUserId('guest');
        $guest->reject();
        $guest->setLastname('GUEST');

        $guestEmail = $guest->getEmail();
        $I->assertEmpty($guestEmail);
    }
}

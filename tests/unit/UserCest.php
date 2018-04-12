<?php


class UserCest
{
    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }

    /**
     * This test verifies that the getEmail() method works on guest user items. Due to the fact that the behaviour
     * of such items differs from a normal user item, special handling is required. For example, a guest user does not
     * have a related portal user item.
     *
     * This test was introduced to fix a regression that throws a fatal error when a guest user was trying to enter
     * a community room on a very specific portal configuration but is expected to be a general problem.
     *
     * @param UnitTester $I
     */
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

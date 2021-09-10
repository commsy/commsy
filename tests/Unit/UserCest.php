<?php

namespace App\Tests\Unit;

use App\Entity\Account;
use App\Entity\AuthSource;
use App\Entity\Portal;
use App\Entity\RoomPrivat;
use App\Entity\User;
use App\Facade\AccountCreatorFacade;
use App\Facade\PortalCreatorFacade;
use App\Services\LegacyEnvironment;
use App\Form\DataTransformer\UserTransformer;
use App\Tests\UnitTester;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class UserCest
{
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

    public function createAccount(UnitTester $I)
    {
        $portal = new Portal();
        $portal->setStatus(1);
        $portal->setTitle('Testportal');

        /** @var PortalCreatorFacade $portalCreator */
        $portalCreator = $I->grabService(PortalCreatorFacade::class);
        $portalCreator->persistPortal($portal);

        /**
         * This is a mandatory workaround to ensure a private room is created when using the legacy managers. They
         * rely on the current portal id to be set. In production this is done by the LegacySubscriber.
         */
        /** @var \cs_environment $legacyEnvironment */
        $legacyEnvironment = $I->grabService(LegacyEnvironment::class)->getEnvironment();
        $legacyEnvironment->setCurrentPortalID($portal->getId());

        $localAuthSource = $portal->getAuthSources()->filter(function(AuthSource $authSource) {
            return $authSource->getType() === 'local';
        })->first();

        $account = new Account();
        $account->setAuthSource($localAuthSource);
        $account->setContextId($portal->getId());
        $account->setLanguage('de');
        $account->setFirstname('Firstname');
        $account->setLastname('lastname');
        $account->setEmail('mail@test.de');
        $account->setUsername('username');
        $account->setPlainPassword('ZSzq9z3aH8xDmGnLip');

        /** @var UserPasswordEncoderInterface $passwordEncoder */
        $passwordEncoder = $I->grabService('security.password_encoder');
        $password = $passwordEncoder->encodePassword($account, $account->getPlainPassword());
        $account->setPassword($password);

        /** @var AccountCreatorFacade $accountFacade */
        $accountFacade = $I->grabService(AccountCreatorFacade::class);
        $accountFacade->persistNewAccount($account);

        // We expect to see:
        // 1. A new account entry
        $I->seeInRepository(Account::class, ['username' => 'username']);
        // 2. A private room created for the new user
        $I->assertEquals(1, sizeof($I->grabEntitiesFromRepository(RoomPrivat::class, [])));
        // 3. Two entries in the user table (private room user + portal user)
        $I->assertEquals(2, sizeof($I->grabEntitiesFromRepository(User::class, ['userId' => 'username'])));
    }

    /**
     * Check that changing the account email address will affect the auth table, the portal user and the private room,
     * but not normal workspace users.
     *
     * @param UnitTester $I
     */
    public function changeAccountEmailTest(UnitTester $I)
    {
        /** @var \cs_environment $legacyEnvironment */
        $legacyEnvironment = $I->grabService('commsy_legacy.environment')->getEnvironment();

        /** @var \cs_user_item $rootUser */
        $rootUser = $legacyEnvironment->getRootUserItem();

        $portalItem = $I->createPortal('Portal', $rootUser);
        $legacyEnvironment->setCurrentContextID($portalItem->getItemId());

        $portalUser = $I->createPortalUser('portalUser', 'Vorname', 'Nachname', 'user@commsy.net', 'passwort', $portalItem);
        $legacyEnvironment->setCurrentUser($portalUser);

        $projectRoom = $I->createProjectRoom('Project Room', $portalUser, $portalItem);

        /** @var UserTransformer $userTransformer */
        $userTransformer = $I->grabService(UserTransformer::class);
        $userData = $userTransformer->transform($portalUser);
        $userData['emailAccount'] = 'new@commsy.net';
        $userTransformer->applyTransformation($portalUser, $userData);
        $portalUser->save();


        // Check private room user
        $privateRoomUser = $portalUser->getRelatedPrivateRoomUserItem();
        $I->seeInDatabase('user', [
            'item_id' => $privateRoomUser->getItemID(),
            'email' => 'new@commsy.net',
        ]);

        // Check portal user
        $I->seeInDatabase('user', [
            'item_id' => $portalUser->getItemID(),
            'email' => 'new@commsy.net',
        ]);

        // Check auth table
        $I->seeInDatabase('auth', [
            'commsy_id' => $portalUser->getContextID(),
            'user_id' => $portalUser->getUserID(),
            'email' => 'new@commsy.net',
        ]);

        // Check projet user
        $projectRoomUser = $portalUser->getRelatedUserItemInContext($projectRoom->getItemID());
        $I->seeInDatabase('user', [
            'item_id' => $projectRoomUser->getItemID(),
            'email' => 'user@commsy.net',
        ]);
    }
}

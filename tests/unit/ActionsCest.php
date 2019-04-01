<?php


class ActionsCest
{
    public function deleteUser(UnitTester $I)
    {
        /** @var \cs_environment $legacyEnvironment */
        $legacyEnvironment = $I->grabService('commsy_legacy.environment')->getEnvironment();

        /** @var \cs_user_item $rootUser */
        $rootUser = $legacyEnvironment->getRootUserItem();

        $portalItem = $I->createPortal('Portal', $rootUser);
        $legacyEnvironment->setCurrentContextID($portalItem->getItemId());

        $portalUser = $I->createPortalUser('portalUser', 'Vorname', 'Nachname', 'user@commsy.net', 'passwort', $portalItem);
        $legacyEnvironment->setCurrentUser($portalUser);

        $projectRoom = $I->createProjectRoom('Project', $portalUser, $portalItem);

        $moderator = $I->createPortalUser('moderator', 'Mod', 'erator', 'moderator@commsy.net', 'password', $portalItem);
        $user1 = $I->createPortalUser('user1', 'First', 'User', 'user1@commsy.net', 'password', $portalItem);
        $user2 = $I->createPortalUser('user2', 'Second', 'User', 'user2@commsy.net', 'password', $portalItem);

        $roomModerator = $moderator->cloneData();
        $roomUser1 = $user1->cloneData();
        $roomUser2 = $user2->cloneData();

        $roomModerator->setContextID($projectRoom->getItemID());
        $roomUser1->setContextID($projectRoom->getItemID());
        $roomUser2->setContextID($projectRoom->getItemID());

        $roomModerator->makeModerator();
        $roomUser1->makeUser();
        $roomUser2->makeUser();

        $roomModerator->save();
        $roomUser1->save();
        $roomUser2->save();

        $I->seeInDatabase('commsy.user', [
            'item_id' => $roomUser1->getItemID(),
        ]);
        $I->seeInDatabase('commsy.user', [
            'item_id' => $roomUser2->getItemID(),
        ]);

        $legacyEnvironment->setCurrentContextID($projectRoom->getItemID());
        $legacyEnvironment->setCurrentUser($roomModerator);

        $itemIds = [
            $roomUser1->getItemID(),
            $roomUser2->getItemID(),
        ];

        /** @var \Commsy\LegacyBundle\Utils\UserService $userService */
        $userService = $I->grabService('commsy_legacy.user_service');
        $users = $userService->getUsersById($projectRoom->getItemID(), $itemIds);

        $action = $I->grabService('commsy.action.delete.generic');
        $action->execute($projectRoom, $users);

        $deletionDateUser1 = $I->grabFromDatabase('commsy.user', 'deletion_date', [
            'item_id' => $roomUser1->getItemID(),
        ]);
        $deletionDateUser2 = $I->grabFromDatabase('commsy.user', 'deletion_date', [
            'item_id' => $roomUser2->getItemID(),
        ]);

        $I->assertNotNull($deletionDateUser1);
        $I->assertNotNull($deletionDateUser2);
    }

    public function deleteGroup(UnitTester $I)
    {
        /** @var \cs_environment $legacyEnvironment */
        $legacyEnvironment = $I->grabService('commsy_legacy.environment')->getEnvironment();

        /** @var \cs_user_item $rootUser */
        $rootUser = $legacyEnvironment->getRootUserItem();

        $portalItem = $I->createPortal('Portal', $rootUser);
        $legacyEnvironment->setCurrentContextID($portalItem->getItemId());

        $portalUser = $I->createPortalUser('portalUser', 'Vorname', 'Nachname', 'user@commsy.net', 'passwort', $portalItem);
        $legacyEnvironment->setCurrentUser($portalUser);

        $projectRoom = $I->createProjectRoom('Project', $portalUser, $portalItem);

        $moderator = $I->createPortalUser('moderator', 'Mod', 'erator', 'moderator@commsy.net', 'password', $portalItem);

        $roomModerator = $moderator->cloneData();
        $roomModerator->setContextID($projectRoom->getItemID());
        $roomModerator->makeModerator();
        $roomModerator->save();

        $legacyEnvironment->setCurrentContextID($projectRoom->getItemID());
        $legacyEnvironment->setCurrentUser($roomModerator);

        /** @var \Commsy\LegacyBundle\Utils\GroupService $groupService */
        $groupService = $I->grabService('commsy_legacy.group_service');
        $group = $groupService->getNewGroup();
        $group->save();

        $I->seeInDatabase('commsy.labels', [
            'item_id' => $group->getItemID(),
        ]);

        $itemIds = [
            $group->getItemID(),
        ];

        $groups = $groupService->getGroupsById($projectRoom->getItemID(), $itemIds);

        $action = $I->grabService('commsy.action.delete.generic');
        $action->execute($projectRoom, $groups);

        $deletionDateGroup = $I->grabFromDatabase('commsy.labels', 'deletion_date', [
            'item_id' => $group->getItemID(),
        ]);

        $I->assertNotNull($deletionDateGroup);
    }

    public function nonItemDelete(UnitTester $I)
    {
        /** @var \cs_environment $legacyEnvironment */
        $legacyEnvironment = $I->grabService('commsy_legacy.environment')->getEnvironment();

        /** @var \App\Action\Delete\DeleteAction $action */
        $action = $I->grabService('commsy.action.delete.generic');
        $response = $action->execute(new \cs_room_item($legacyEnvironment), []);

        $I->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
    }
}
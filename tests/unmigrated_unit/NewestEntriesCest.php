<?php

namespace App\Tests\Unit;

use App\Tests\UnitTester;


class NewestEntriesCest
{
    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function vanillaRoomTest(UnitTester $I)
    {
        /** @var \cs_environment $legacyEnvironment */
        $legacyEnvironment = $I->grabService('commsy_legacy.environment')->getEnvironment();

        /** @var \cs_user_item $rootUser */
        $rootUser = $legacyEnvironment->getRootUserItem();

        $portalItem = $I->createPortal('Portal', $rootUser);
        $legacyEnvironment->setCurrentContextID($portalItem->getItemId());

        $portalUser = $I->createPortalUser('portalUser', 'Vorname', 'Nachname', 'user@commsy.net', 'passwort', $portalItem);
        $legacyEnvironment->setCurrentUser($portalUser);

        $projectRoom = $I->createProjectRoom('Room', $portalUser, $portalItem);

        /** @var \App\RoomFeed\RoomFeedGenerator $roomFeedGenerator */
        $roomFeedGenerator = $I->grabService('commsy.room_feed_generator');
        $roomFeedEntries = $roomFeedGenerator->getRoomFeedList($projectRoom->getItemId(), 10, null);
        $I->assertEquals(1, sizeof($roomFeedEntries));
    }

    public function reloadTest(UnitTester $I)
    {
        /** @var \cs_environment $legacyEnvironment */
        $legacyEnvironment = $I->grabService('commsy_legacy.environment')->getEnvironment();

        /** @var \cs_user_item $rootUser */
        $rootUser = $legacyEnvironment->getRootUserItem();

        $portalItem = $I->createPortal('Portal', $rootUser);
        $legacyEnvironment->setCurrentContextID($portalItem->getItemId());

        $portalUser = $I->createPortalUser('portalUser', 'Vorname', 'Nachname', 'user@commsy.net', 'passwort', $portalItem);
        $legacyEnvironment->setCurrentUser($portalUser);

        $projectRoom = $I->createProjectRoom('Room', $portalUser, $portalItem);

        /** @var \cs_list $projectUserList */
        $projectUserList = $projectRoom->getUserList();
        $I->assertEquals(1, $projectUserList->getCount());

        $lastFeedItem = $projectUserList->getFirst();

        /** @var \App\RoomFeed\RoomFeedGenerator $roomFeedGenerator */
        $roomFeedGenerator = $I->grabService('commsy.room_feed_generator');

        // ensure user entry in items and user table have the same creation and modification date
        $now = new \DateTimeImmutable();
        $I->updateInDatabase('commsy.items', [
            'items.modification_date' => $now->format('Y-m-d H:i:s'),
        ], [
            'items.item_id' => $lastFeedItem->getItemId(),
        ]);
        $I->updateInDatabase('commsy.user', [
            'user.creation_date' => $now->format('Y-m-d H:i:s'),
            'user.modification_date' => $now->format('Y-m-d H:i:s'),
        ], [
            'user.item_id' => $lastFeedItem->getItemId(),
        ]);

        $legacyEnvironment->unsetAllInstancesExceptTranslator();
        $roomFeedEntries = $roomFeedGenerator->getRoomFeedList($projectRoom->getItemId(), 10, $lastFeedItem->getItemId());
        $I->assertEmpty($roomFeedEntries);

        // ensure that creation and modification date of user entry in user table is 1 second behind of items table
        $nowMinusOneSecond = $now->sub(new \DateInterval('PT1S'));
        $I->updateInDatabase('commsy.items', [
            'items.modification_date' => $now->format('Y-m-d H:i:s'),
        ], [
            'items.item_id' => $lastFeedItem->getItemId(),
        ]);
        $I->updateInDatabase('commsy.user', [
            'user.creation_date' => $nowMinusOneSecond->format('Y-m-d H:i:s'),
            'user.modification_date' => $nowMinusOneSecond->format('Y-m-d H:i:s'),
        ], [
            'user.item_id' => $lastFeedItem->getItemId(),
        ]);

        $legacyEnvironment->unsetAllInstancesExceptTranslator();
        $roomFeedEntries = $roomFeedGenerator->getRoomFeedList($projectRoom->getItemId(), 10, $lastFeedItem->getItemId());
        $I->assertEmpty($roomFeedEntries);
    }
}

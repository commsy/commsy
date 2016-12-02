<?php

namespace Tests\Cron;

use Tests\DatabaseTestCase;


class DeleteInactiveUserTest extends DatabaseTestCase
{
    protected function setUp()
    {
        parent::setUp();

        \DateTesting::$dateTime = null;
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    public function getDataSet()
    {
        return $this->createMySQLXMLDataSet('tests/Cron/delete_inactive_user_dataset.xml');
    }

// TODO: remove portal content configuration

    /**
     * Tests if a user is completely deleted with all his room memberships
     */
    public function testFullDelete()
    {
        global $environment;
        $environment->setCurrentContextID(101);

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $portalUser = $userManager->getItem(158);
        $privateRoomUser = $userManager->getItem(160);
        $firstRoomUser = $userManager->getItem(173);
        $secondRoomUser = $userManager->getItem(178);

        $this->assertEquals(101, $portalUser->getContextID());
        $this->assertFalse($portalUser->isDeleted());
        $this->assertTrue($privateRoomUser->getContextItem()->isPrivateRoom());
        $this->assertFalse($privateRoomUser->isDeleted());
        $this->assertEquals(166, $firstRoomUser->getContextID());
        $this->assertFalse($firstRoomUser->isDeleted());
        $this->assertEquals(171, $secondRoomUser->getContextID());
        $this->assertFalse($secondRoomUser->isDeleted());

        $portalUser->deleteUserCausedByInactivity();

        $portalUser = $userManager->getItem(158);
        $privateRoomUser = $userManager->getItem(160);
        $firstRoomUser = $userManager->getItem(173);
        $secondRoomUser = $userManager->getItem(178);

        $this->assertTrue($portalUser->isDeleted());
        $this->assertTrue($privateRoomUser->isDeleted());
        $this->assertTrue($firstRoomUser->isDeleted());
        $this->assertTrue($secondRoomUser->isDeleted());
    }

    /**
     * Two users with the same or a similar user id in terms of case sensitivity
     * must not be deleted if only one is affected
     */
    public function testDeleteSameUserDifferentPortals()
    {
        global $environment;
        $environment->setCurrentContextID(101);

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $firstUser = $userManager->getItem(118);
        $secondUser = $userManager->getItem(122);

        $this->assertInstanceOf('cs_user_item', $firstUser);
        $this->assertInstanceOf('cs_user_item', $secondUser);
        $this->assertEquals(101, $firstUser->getContextID());
        $this->assertEquals(107, $secondUser->getContextID());
        $this->assertEquals($firstUser->getUserID(), $secondUser->getUserID());

        $firstUser->deleteUserCausedByInactivity();

        $firstUser = $userManager->getItem(118);
        $secondUser = $userManager->getItem(122);

        $this->assertTrue($firstUser->isDeleted());
        $this->assertFalse($secondUser->isDeleted());
    }

    /**
     * The user has no room membership and should be deleted
     */
    public function testDeleteNoRoom()
    {
        global $environment;
        $environment->setCurrentContextID(101);

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $user = $userManager->getItem(118);

        # pre condition
        $projectRooms = $user->getRelatedProjectList();
        $communityRooms = $user->getRelatedCommunityList();
        $allRooms = new \cs_list();
        $allRooms->addList($projectRooms);
        $allRooms->addList($communityRooms);

        $this->assertTrue($allRooms->isEmpty());
        $this->assertEquals(101, $user->getContextID());

        $user->deleteUserCausedByInactivity();

        $user = $userManager->getItem(118);

        $this->assertTrue($user->isDeleted());
    }

    /**
     * The user is the last moderator of a room and must not be deleted
     */
    public function testDeleteLastModerator()
    {
        global $environment;
        $environment->setCurrentContextID(101);

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $moderator = $userManager->getItem(131);

        $roomManager = $environment->getRoomManager();
        $roomManager->setCacheOff();

        $room = $roomManager->getItem(130);

        # pre condition
        $this->assertInstanceOf('cs_user_item', $moderator);
        $this->assertEquals(130, $moderator->getContextID());
        $this->assertFalse($moderator->isModerator());
        $this->assertEquals(1, $room->getModeratorList()->getCount());

        $moderator->getRelatedPortalUserItem()->deleteUserCausedByInactivity();

        $moderator = $userManager->getItem(131);

        $this->assertFalse($moderator->isDeleted());
    }

    /**
     * The user is a normal member of a room and should be deleted
     */
    public function testDeleteMember()
    {
        global $environment;
        $environment->setCurrentContextID(101);

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $user = $userManager->getItem(135);

        # pre condition
        $this->assertInstanceOf('cs_user_item', $user);
        $this->assertEquals(113, $user->getContextID());
        $this->assertFalse($user->isModerator());

        $user->getRelatedPortalUserItem()->deleteUserCausedByInactivity();

        $user = $userManager->getItem(126);

        $this->assertTrue($user->isDeleted());
    }

    /**
     * The user is not the last moderator of a room and can be deleted
     */
    public function testDeleteNotLastModerator()
    {
        global $environment;
        $environment->setCurrentContextID(101);

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $firstModerator = $userManager->getItem(147);
        $secondModerator = $userManager->getItem(155);

        # pre condition
        $this->assertInstanceOf('cs_user_item', $firstModerator);
        $this->assertInstanceOf('cs_user_item', $secondModerator);
        $this->assertEquals(146, $firstModerator->getContextID());
        $this->assertEquals(146, $secondModerator->getContextID());
        $this->assertTrue($firstModerator->isModerator());
        $this->assertTrue($secondModerator->isModerator());

        $firstModerator->getRelatedPortalUserItem()->deleteUserCausedByInactivity();

        $firstModerator = $userManager->getItem(147);
        $secondModerator = $userManager->getItem(155);

        $this->assertTrue($firstModerator->isDeleted());
        $this->assertFalse($secondModerator->isDeleted());
    }

    /**
     * Test for simulating the different stages when deleting users
     * because of inactivity
     */
    public function testInactivityFlags()
    {
        global $environment;
        $environment->setCurrentContextID(99);

        // get all portal user
        $userManager = $environment->getUserManager();
        $userManager->resetLimits();
        $userManager->setContextArrayLimit([101, 107]);
        $userManager->setUserLimit();
        $userManager->select();

        $portalUserList = $userManager->get();

        $this->assertEquals(10, $portalUserList->getCount(), 'Pre-Condition');

        // start date
        $timestamp = 1500000000; // 2017-07-14 04:40:00
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        // update last login for all portal user
        $portalUser = $portalUserList->getFirst();
        while ($portalUser) {
            $userManager->updateLastLoginOf($portalUser);

            $portalUser = $portalUserList->getNext();
        }

        $this->assertEquals(10, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE user.lastlogin = "' . \DateTesting::$dateTime . '"'
        )->getRowCount());

        $server = $environment->getServerItem();

        /**
         * Run cron script for the first time. Since we updated last login, no user
         * should be notified about inactivity
         */
        $server->_cronInactiveUserDelete();

        $this->assertEquals(0, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.extras LIKE "%NOTIFY_LOCK_DATE%" OR
                user.extras LIKE "%NOTIFY_DELETE_DATE%" OR
                user.extras LIKE "%MAIL_SEND_NEXT_LOCK%" OR
                user.extras LIKE "%MAIL_SEND_NEXT_DELETE%" OR
                user.extras LIKE "%MAIL_SEND_LOCK%" OR
                user.extras LIKE "%MAIL_SEND_DELETE%" OR
                user.extras LIKE "%MAIL_SEND_LOCKED%" OR
                user.extras LIKE "%LOCK_SEND_MAIL_DATE%" OR
                user.extras LIKE "%MAIL_SEND_DELETE%"'
        )->getRowCount());

        /**
         * This is one day before lock notification deadline, expect no extras
         */
        $timestamp = 1500000000 + (365 - 21) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server->_cronInactiveUserDelete();

        $this->assertEquals(0, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.extras LIKE "%NOTIFY_LOCK_DATE%" OR
                user.extras LIKE "%NOTIFY_DELETE_DATE%" OR
                user.extras LIKE "%MAIL_SEND_NEXT_LOCK%" OR
                user.extras LIKE "%MAIL_SEND_NEXT_DELETE%" OR
                user.extras LIKE "%MAIL_SEND_LOCK%" OR
                user.extras LIKE "%MAIL_SEND_DELETE%" OR
                user.extras LIKE "%MAIL_SEND_LOCKED%" OR
                user.extras LIKE "%LOCK_SEND_MAIL_DATE%" OR
                user.extras LIKE "%MAIL_SEND_DELETE%"'
        )->getRowCount());

        /**
         * Lock notification deadline, expect notification extra
         */
        $timestamp = 1500000000 + (365 - 20) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server->_cronInactiveUserDelete();

        $this->assertEquals(10, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.extras LIKE "%NOTIFY_LOCK_DATE%" AND
                user.extras NOT LIKE "%MAIL_SEND_LOCK%" AND
                user.extras NOT LIKE "%MAIL_SEND_LOCKED%"'
        )->getRowCount());

        /**
         * One day before lock deadline
         */
        $timestamp = 1500000000 + (365 - 1) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server->_cronInactiveUserDelete();

        $this->assertEquals(10, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.extras LIKE "%NOTIFY_LOCK_DATE%" AND
                user.extras LIKE "%MAIL_SEND_LOCK%" AND
                user.extras NOT LIKE "%MAIL_SEND_LOCKED%"'
        )->getRowCount());

        /**
         * Lock deadline
         */
        $timestamp = 1500000000 + (365) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server->_cronInactiveUserDelete();

        $this->assertEquals(10, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.extras LIKE "%NOTIFY_LOCK_DATE%" AND
                user.extras LIKE "%MAIL_SEND_LOCK%" AND
                user.extras LIKE "%MAIL_SEND_LOCKED%" AND
                user.extras LIKE "%LOCK_SEND_MAIL_DATE%" AND
                user.status = 0'
        )->getRowCount());

        /**
         * One day before deletion notification deadline, expect no deletion extras
         */
        $timestamp = 1500000000 + (365 * 2 - 21) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server->_cronInactiveUserDelete();

        $this->assertEquals(10, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.extras NOT LIKE "%NOTIFY_DELETE_DATE%" AND
                user.extras NOT LIKE "%MAIL_SEND_NEXT_DELETE%" AND
                user.extras NOT LIKE "%MAIL_SEND_DELETE%" AND
                user.status = 0'
        )->getRowCount());

        /**
         * Deletion notification deadline
         */
        $timestamp = 1500000000 + (365 * 2 - 20) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server->_cronInactiveUserDelete();

        $this->assertEquals(10, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.extras LIKE "%NOTIFY_DELETE_DATE%" AND
                user.status = 0'
        )->getRowCount());

        /**
         * One day before deletion
         */
        $timestamp = 1500000000 + (365 * 2 - 1) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server->_cronInactiveUserDelete();

        $this->assertEquals(10, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.extras LIKE "%NOTIFY_DELETE_DATE%" AND
                user.extras LIKE "%MAIL_SEND_NEXT_DELETE%" AND
                user.status = 0'
        )->getRowCount());

        /**
         * Deletion
         */
        $timestamp = 1500000000 + (365 * 2) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server->_cronInactiveUserDelete();

        $this->assertEquals(10, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.deletion_date IS NOT NULL AND
                user.deleter_id IS NOT NULL AND
                user.extras LIKE "%NOTIFY_DELETE_DATE%" AND
                user.extras LIKE "%MAIL_SEND_NEXT_DELETE%" AND
                user.extras LIKE "%MAIL_SEND_DELETE%" AND
                user.status = 0'
        )->getRowCount());


//        NOTIFY_LOCK_DATE
//        NOTIFY_DELETE_DATE
//
//        MAIL_SEND_NEXT_DELETE
//
//        MAIL_SEND_LOCK
//        MAIL_SEND_DELETE
//
//        MAIL_SEND_LOCKED
//        LOCK_SEND_MAIL_DATE
//        MAIL_SEND_DELETE
    }
}
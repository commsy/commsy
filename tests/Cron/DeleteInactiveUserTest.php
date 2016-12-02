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

//    public function testDeleteSingleMembership()
//    {
//        global $environment;
//        $environment->setCurrentContextID(101);
//
//        # pre condition
//        $this->assertEquals(16, $this->getConnection()->getRowCount('user'), 'Pre-Condition');
//
//        /**
//         * We delete a user with a single room membership, the room itself has two members.
//         * The user must be deleted, but the room should stay intact.
//         */
//        $roomManager = $environment->getRoomManager();
//        $roomManager->deleteRoomOfUserAndUserItemsInactivity('WITH_ROOM');
//
//        $activeUserQueryTable = $this->getConnection()->createQueryTable(
//            'user', 'SELECT * FROM user WHERE user.deletion_date IS NULL'
//        );
//        $activeRoomQueryTable = $this->getConnection()->createQueryTable(
//            'room', 'SELECT * FROM room WHERE room.deletion_DATE IS NULL'
//        );
//
//        $this->assertEquals(11, $activeUserQueryTable->getRowCount(), 'User with room');
//        $this->assertEquals(2, $activeRoomQueryTable->getRowCount());
//    }
//
//    public function testDeleteMultiMembership()
//    {
//        global $environment;
//        $environment->setCurrentContextID(101);
//
//        # pre condition
//        $this->assertEquals(16, $this->getConnection()->getRowCount('user'), 'Pre-Condition');
//
//        /**
//         * We delete a user with multiple room memberships and expect that the user
//         * and only his room will be deleted
//         */
//        $roomManager = $environment->getRoomManager();
//        $roomManager->deleteRoomOfUserAndUserItemsInactivity('WITH_TWO_ROOMS');
//
//        $activeUserQueryTable = $this->getConnection()->createQueryTable(
//            'user', 'SELECT * FROM user WHERE user.deletion_date IS NULL'
//        );
//        $activeRoomQueryTable = $this->getConnection()->createQueryTable(
//            'room', 'SELECT * FROM room WHERE room.deletion_DATE IS NULL'
//        );
//
//        $this->assertEquals(10, $activeUserQueryTable->getRowCount(), 'User with two room');
//        $this->assertEquals(1, $activeRoomQueryTable->getRowCount());
//    }
//


// TODO: remove portal content configuration

    /**
     * Two users with the same or a similar user id in terms of case sensitivity
     * must not be deleted if only one is affected
     */
    public function testDeleteSameUserDifferentPortals()
    {

    }

    /**
     * The user has no room membership and should be deleted
     */
    public function testDeleteNoRoom()
    {
//        global $environment;
//        $environment->setCurrentContextID(101);
//
//        # pre condition
//        $this->assertEquals(16, $this->getConnection()->getRowCount('user'), 'Pre-Condition');
//
//        $roomManager = $environment->getRoomManager();
//        $roomManager->deleteRoomOfUserAndUserItemsInactivity('NO_ROOM');
//
//        $this->assertEquals(12, $this->getConnection()->createQueryTable(
//            'user', 'SELECT * FROM user WHERE user.deletion_date IS NULL'
//        )->getRowCount(), 'User without room');
//        $this->assertEquals(2, $this->getConnection()->createQueryTable(
//            'room', 'SELECT * FROM room WHERE room.deletion_DATE IS NULL'
//        )->getRowCount());
    }

    public function testDeleteLastModerator()
    {

    }

    public function testDeleteLastMember()
    {

    }

    public function testDeleteNotLastModerator()
    {

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

        $this->assertEquals(6, $portalUserList->getCount(), 'Pre-Condition');

        // start date
        $timestamp = 1500000000; // 2017-07-14 04:40:00
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        // update last login for all portal user
        $portalUser = $portalUserList->getFirst();
        while ($portalUser) {
            $userManager->updateLastLoginOf($portalUser);

            $portalUser = $portalUserList->getNext();
        }

        $this->assertEquals(6, $this->getConnection()->createQueryTable(
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

        $this->assertEquals(6, $this->getConnection()->createQueryTable(
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

        $this->assertEquals(6, $this->getConnection()->createQueryTable(
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

        $this->assertEquals(6, $this->getConnection()->createQueryTable(
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

        $this->assertEquals(6, $this->getConnection()->createQueryTable(
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

        $this->assertEquals(6, $this->getConnection()->createQueryTable(
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

        $this->assertEquals(6, $this->getConnection()->createQueryTable(
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

        $this->assertEquals(6, $this->getConnection()->createQueryTable(
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
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

    /**
     * Tests the deletion process.
     */
    public function testDelete()
    {
        global $environment;
        $environment->setCurrentContextID(99);

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $itemManager = $environment->getItemManager();
        $itemManager->setCacheOff();

        $user = $userManager->getItem(109);

        $this->assertInstanceOf('cs_user_item', $user);
        $this->assertEquals(101, $user->getContextID());

        $user->deleteUserCausedByInactivity();

        // test users
        $this->assertTrue($userManager->getItem(109)->isDeleted());
        $this->assertTrue($userManager->getItem(111)->isDeleted());
        $this->assertTrue($userManager->getItem(139)->isDeleted());

        // test items
        $this->assertTrue($itemManager->getItem(109)->isDeleted());
        $this->assertTrue($itemManager->getItem(111)->isDeleted());
        $this->assertTrue($itemManager->getItem(139)->isDeleted());

        // test auth table
        $this->assertEquals(0, $this->getConnection()->createQueryTable(
            'auth', 'SELECT * FROM auth WHERE
                auth.commsy_id = 101 AND
                auth.user_id = "DELETE"'
        )->getRowCount(), 'testDelete');
        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'auth', 'SELECT * FROM auth WHERE
                auth.commsy_id = 103 AND
                auth.user_id = "DELETE"'
        )->getRowCount(), 'testDelete');
    }

    /**
     * Tests if content is not overwritten according to global configuration.
     */
    public function testDeleteNoOverride()
    {
        global $environment;
        $environment->setCurrentContextID(99);

        global $c_datenschutz_disable_overwriting;
        $c_datenschutz_disable_overwriting = true;

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $user = $userManager->getItem(109);

        $this->assertInstanceOf('cs_user_item', $user);
        $this->assertEquals(101, $user->getContextID());

        $user->deleteUserCausedByInactivity();

        $materialManager = $environment->getMaterialManager();
        $material = $materialManager->getItem(142);

        $this->assertFalse($material->isDeleted());
    }

    /**
     * Two users with the same or a similar user id in terms of case sensitivity
     * must not be deleted if only one is affected.
     */
    public function testDeleteSameUserDifferentPortals()
    {
        global $environment;
        $environment->setCurrentContextID(99);

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $firstUser = $userManager->getItem(109);
        $secondUser = $userManager->getItem(105);

        $this->assertInstanceOf('cs_user_item', $firstUser);
        $this->assertInstanceOf('cs_user_item', $secondUser);
        $this->assertEquals(101, $firstUser->getContextID());
        $this->assertEquals(103, $secondUser->getContextID());
        $this->assertEquals($firstUser->getUserID(), $secondUser->getUserID());

        $firstUser->deleteUserCausedByInactivity();

        $firstUser = $userManager->getItem(109);
        $secondUser = $userManager->getItem(105);

        $this->assertTrue($firstUser->isDeleted());
        $this->assertFalse($secondUser->isDeleted());
    }

    /**
     * Users being the last moderator of a room must not be deleted.
     */
    public function testInactivityLastModerator()
    {
        global $environment;
        $environment->setCurrentContextID(99);

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $user = $userManager->getItem(113);

        $this->assertInstanceOf('cs_user_item', $user);
        $this->assertEquals(101, $user->getContextID());

        // 2017-07-14 04:40:00
        $timestamp = 1500000000;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 113 AND
                user.extras NOT LIKE "%NOTIFY_LOCK_DATE%"'
        )->getRowCount(), 'testInactivityLastModerator');

        $server = $environment->getServerItem();
        $server->_cronInactiveUserDelete();

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 113 AND
                user.extras NOT LIKE "%NOTIFY_LOCK_DATE%"'
        )->getRowCount(), 'testInactivityLastModerator');
    }

    /**
     * Tests the handling of users already being locked.
     * Already locked users should skip the locking process and
     * get notified about deletion.
     */
    public function testInactivityAlreadyLockedUser()
    {
        global $environment;
        $environment->setCurrentContextID(99);

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $user = $userManager->getItem(122);

        $this->assertInstanceOf('cs_user_item', $user);
        $this->assertEquals(101, $user->getContextID());
        $this->assertEquals(0, $user->getStatus());

        // 2017-07-14 04:40:00
        $timestamp = 1500000000;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server = $environment->getServerItem();

        // first run will set lock flags
        $server->_cronInactiveUserDelete();

        $timestamp = 1500000000 + (365 - 1) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        // second run will send deletion notification
        $server->_cronInactiveUserDelete();

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 122 AND
                user.extras LIKE "%NOTIFY_DELETE_DATE%" AND
                user.status = 0'
        )->getRowCount(), 'testInactivityAlreadyLockedUser');
    }

    /**
     * Already deleted users must not be considered when being inactive.
     */
    public function testInactivityAlreadyDeletedUser()
    {
        global $environment;
        $environment->setCurrentContextID(99);

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $user = $userManager->getItem(126);

        $this->assertInstanceOf('cs_user_item', $user);
        $this->assertEquals(101, $user->getContextID());
        $this->assertTrue($user->isDeleted());

        // 2017-07-14 04:40:00
        $timestamp = 1500000000;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 126 AND
                user.deletion_date IS NOT NULL AND
                user.deleter_id IS NOT NULL AND
                user.extras NOT LIKE "%NOTIFY_LOCK_DATE%"'
        )->getRowCount(), 'testInactivityAlreadyDeletedUser');

        $server = $environment->getServerItem();
        $server->_cronInactiveUserDelete();

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 126 AND
                user.deletion_date IS NOT NULL AND
                user.deleter_id IS NOT NULL AND
                user.extras NOT LIKE "%NOTIFY_LOCK_DATE%"'
        )->getRowCount(), 'testInactivityAlreadyDeletedUser');
    }

    /**
     * Tests inactivity handling if user logged in after information
     * about inactivity.
     */
    public function testInactivityUserWakeUp()
    {
        global $environment;
        $environment->setCurrentContextID(99);

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $user = $userManager->getItem(109);

        $this->assertInstanceOf('cs_user_item', $user);
        $this->assertEquals(101, $user->getContextID());

        // 2017-07-14 04:40:00
        $timestamp = 1500000000;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 109 AND
                user.extras NOT LIKE "%NOTIFY_LOCK_DATE%"'
        )->getRowCount(), 'testInactivityLastLogin');

        $server = $environment->getServerItem();
        $server->_cronInactiveUserDelete();

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 109 AND
                user.extras LIKE "%NOTIFY_LOCK_DATE%"'
        )->getRowCount(), 'testInactivityUserWakeUp');

        $user = $userManager->getItem(109);
        $user->updateLastLogin();
        $user->resetInactivity();

        $server->_cronInactiveUserDelete();

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 109 AND
                user.extras NOT LIKE "%NOTIFY_LOCK_DATE%"'
        )->getRowCount(), 'testInactivityUserWakeUp');
    }

    /**
     * Tests if user is only processed if he was inactive.
     */
    public function testInactivityLastLogin()
    {
        global $environment;
        $environment->setCurrentContextID(99);

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $user = $userManager->getItem(130);

        $this->assertInstanceOf('cs_user_item', $user);
        $this->assertEquals(101, $user->getContextID());

        // 2017-07-14 04:40:00
        $timestamp = 1500000000;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server = $environment->getServerItem();
        $server->_cronInactiveUserDelete();

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 130 AND
                user.extras NOT LIKE "%NOTIFY_LOCK_DATE%"'
        )->getRowCount(), 'testInactivityLastLogin');
    }

    /**
     * Test for simulating the different stages when flagging users
     * because of inactivity.
     */
    public function testInactivityFlags()
    {
        global $environment;
        $environment->setCurrentContextID(99);

        $userManager = $environment->getUserManager();
        $userManager->setCacheOff();

        $user = $userManager->getItem(109);

        $this->assertInstanceOf('cs_user_item', $user);
        $this->assertEquals(101, $user->getContextID());
        $this->assertEquals(2, $user->getStatus());

        // update user last login
        // 2017-07-14 04:40:00
        $timestamp = 1500000000;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);
        $user->updateLastLogin();

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 109 AND
                user.lastlogin = "' . \DateTesting::$dateTime . '"'
        )->getRowCount());

        $server = $environment->getServerItem();

        /**
         * Run cron script for the first time. User should not be
         * notified about inactivity.
         */
        $server->_cronInactiveUserDelete();

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 109 AND
                user.extras NOT LIKE "%NOTIFY_LOCK_DATE%" AND
                user.extras NOT LIKE "%NOTIFY_DELETE_DATE%" AND
                user.extras NOT LIKE "%MAIL_SEND_NEXT_LOCK%" AND
                user.extras NOT LIKE "%MAIL_SEND_NEXT_DELETE%" AND
                user.extras NOT LIKE "%MAIL_SEND_LOCK%" AND
                user.extras NOT LIKE "%MAIL_SEND_DELETE%" AND
                user.extras NOT LIKE "%MAIL_SEND_LOCKED%" AND
                user.extras NOT LIKE "%LOCK_SEND_MAIL_DATE%" AND
                user.extras NOT LIKE "%MAIL_SEND_DELETE%"'
        )->getRowCount());

        /**
         * This is one day before lock notification deadline, expect same state
         */
        $timestamp = 1500000000 + (365 - 15) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server->_cronInactiveUserDelete();

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 109 AND
                user.extras NOT LIKE "%NOTIFY_LOCK_DATE%" AND
                user.extras NOT LIKE "%NOTIFY_DELETE_DATE%" AND
                user.extras NOT LIKE "%MAIL_SEND_NEXT_LOCK%" AND
                user.extras NOT LIKE "%MAIL_SEND_NEXT_DELETE%" AND
                user.extras NOT LIKE "%MAIL_SEND_LOCK%" AND
                user.extras NOT LIKE "%MAIL_SEND_DELETE%" AND
                user.extras NOT LIKE "%MAIL_SEND_LOCKED%" AND
                user.extras NOT LIKE "%LOCK_SEND_MAIL_DATE%" AND
                user.extras NOT LIKE "%MAIL_SEND_DELETE%"'
        )->getRowCount());

        /**
         * Lock notification deadline, expect notification extra
         */
        $timestamp = 1500000000 + (365 - 14) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server->_cronInactiveUserDelete();

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 109 AND
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

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 109 AND
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

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 109 AND
                user.extras LIKE "%NOTIFY_LOCK_DATE%" AND
                user.extras LIKE "%MAIL_SEND_LOCK%" AND
                user.extras LIKE "%MAIL_SEND_LOCKED%" AND
                user.extras LIKE "%LOCK_SEND_MAIL_DATE%" AND
                user.status = 0'
        )->getRowCount());

        /**
         * One day before deletion notification deadline, expect same state
         */
        $timestamp = 1500000000 + (365 * 2 - 2) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server->_cronInactiveUserDelete();

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 109 AND
                user.extras LIKE "%NOTIFY_LOCK_DATE%" AND
                user.extras LIKE "%MAIL_SEND_LOCK%" AND
                user.extras LIKE "%MAIL_SEND_LOCKED%" AND
                user.extras LIKE "%LOCK_SEND_MAIL_DATE%" AND
                user.status = 0'
        )->getRowCount());

        /**
         * Deletion notification deadline
         */
        $timestamp = 1500000000 + (365 * 2 - 1) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server->_cronInactiveUserDelete();

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 109 AND
                user.extras LIKE "%NOTIFY_DELETE_DATE%" AND
                user.extras NOT LIKE "%MAIL_SEND_NEXT_DELETE%" AND
                user.extras NOT LIKE "%MAIL_SEND_DELETE%" AND
                user.status = 0'
        )->getRowCount());

        /**
         * One day before deletion
         */
        $timestamp = 1500000000 + (365 * 2 - 1) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server->_cronInactiveUserDelete();

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 109 AND
                user.extras LIKE "%NOTIFY_DELETE_DATE%" AND
                user.extras LIKE "%MAIL_SEND_NEXT_DELETE%" AND
                user.extras LIKE "%MAIL_SEND_DELETE%" AND
                user.status = 0 AND
                user.deletion_date IS NULL AND
                user.deleter_id IS NULL'
        )->getRowCount());

        /**
         * Deletion
         */
        $timestamp = 1500000000 + (365 * 2) * 24 * 60 * 60;
        \DateTesting::$dateTime = date("Y-m-d H:i:s", $timestamp);

        $server->_cronInactiveUserDelete();

        $this->assertEquals(1, $this->getConnection()->createQueryTable(
            'user', 'SELECT * FROM user WHERE
                user.item_id = 109 AND
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
//        MAIL_SEND_DELETED
    }
}
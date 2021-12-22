<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-03-15
 * Time: 14:36
 */

namespace App\Tests\Unit;

use App\Mail\Messages\ItemDeletedMessage;
use App\Mail\Recipient;
use App\Mail\RecipientFactory;
use App\Tests\UnitTester;

class MailCest
{
    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function invalidEmail(UnitTester $I)
    {
        /** @var \App\Mail\Mailer $mailer */
        $mailer = $I->grabService(\App\Mail\Mailer::class);

        require_once 'classes/cs_room_item.php';
        /** @var \cs_room_item $room */
        $room = \Codeception\Stub::make(\cs_room_item::class, [
            'itemId' => 123,
        ]);

        $message = new \App\Mail\Messages\RoomArchivedMessage($room, 1122);

        $invalid = \Codeception\Stub::make(Recipient::class, [
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'invalidemail',
            'language' => 'de',
        ]);

        $valid = \Codeception\Stub::make(Recipient::class, [
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'valid@test.de',
            'language' => 'de',
        ]);

        $status = $mailer->send($message, $invalid, 'fromSender');
        $I->assertFalse($status);

        $status = $mailer->send($message, $valid, 'fromSender', ['invalidemail' => 'invalid']);
        $I->assertFalse($status);
    }

    public function validEmail(UnitTester $I)
    {
        /** @var \App\Mail\Mailer $mailer */
        $mailer = $I->grabService(\App\Mail\Mailer::class);

        require_once 'classes/cs_room_item.php';
        /** @var \cs_room_item $room */
        $room = \Codeception\Stub::make(\cs_room_item::class, [
            'itemId' => 123,
        ]);

        $message = new \App\Mail\Messages\RoomArchivedMessage($room, 1122);

        $valid = \Codeception\Stub::make(Recipient::class, [
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'valid@test.de',
            'language' => 'de',
        ]);

        $status = $mailer->send($message, $valid, 'fromSender', ['validemail@test.de' => 'name']);
        $I->assertTrue($status);
    }


    public function roomArchivedMessage(UnitTester $I)
    {
        require_once 'classes/cs_room_item.php';
        /** @var \cs_room_item $room */
        $room = \Codeception\Stub::make(\cs_room_item::class, [
            'itemId' => 123,
        ]);

        $message = new \App\Mail\Messages\RoomArchivedMessage($room, 1122);

        $valid = \Codeception\Stub::make(Recipient::class, [
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'valid@test.de',
            'language' => 'de',
        ]);

        /** @var \App\Mail\MessageBuilder $messageBuilder */
        $messageBuilder = $I->grabService(\App\Mail\MessageBuilder::class);
        $generatedMessage = $messageBuilder->generateSwiftMessage($message, 'fromSender', $valid);

        $I->assertRegExp('/http:\/\/somehost\/room\/123/', $generatedMessage->getBody());
    }

    public function itemDeletedMessage(UnitTester $I)
    {
        require_once 'classes/cs_room_item.php';

        /** @var \cs_item $item */
        $item = \Codeception\Stub::make(\cs_item::class, [
            'itemId' => 123,
            'getContextItem' => function() {
                return \Codeception\Stub::make(\cs_room_item::class, [
                    'itemId' => 123,
                    'title' => 'Room',
                ]);
            },
        ]);

        $deleter = \Codeception\Stub::make(\cs_user_item::class, [
            'getFirstname' => function () {
                return 'firstname';
            },
            'getLastname' => function() {
                return 'lastname';
            },
            'getEmail' => function() {
                return 'mail@mail.de';
            },
            'getContextItem' => function() {
                $room = \Codeception\Stub::make(\cs_room_item::class, [
                    'getLanguage' => function() {
                        return 'de';
                    }
                ]);

                return $room;
            },
        ]);

        $recipient = \Codeception\Stub::make(Recipient::class, [
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'valid@test.de',
            'language' => 'de',
        ]);

        $message = new ItemDeletedMessage($item, $deleter);

        /** @var \App\Mail\MessageBuilder $messageBuilder */
        $messageBuilder = $I->grabService(\App\Mail\MessageBuilder::class);
        $generatedMessage = $messageBuilder->generateSwiftMessage($message, 'fromSender', $recipient);

        $I->assertRegExp('/Raum/', $generatedMessage->getBody());
    }

    public function recipientFactoryModerators(UnitTester $I)
    {
        require_once 'classes/cs_room_item.php';
        $moderators = new \cs_list();

        $moderatorWithMail = \Codeception\Stub::make(\cs_user_item::class, [
            'getOpenRoomWantMail' => function() {
                return true;
            },
            'getFirstname' => function () {
                return 'firstname';
            },
            'getLastname' => function() {
                return 'lastname';
            },
            'getEmail' => function() {
                return 'mail@mail.de';
            },
            'getContextItem' => function() {
                $room = \Codeception\Stub::make(\cs_room_item::class, [
                    'getLanguage' => function() {
                        return 'de';
                    }
                ]);

                return $room;
            },
        ]);
        $I->assertTrue($moderatorWithMail->getOpenRoomWantMail());

        $moderatorWithoutMail = \Codeception\Stub::make(\cs_user_item::class, [
            'getOpenRoomWantMail' => function() {
                return false;
            },
            'getFirstname' => function () {
                return 'firstname';
            },
            'getLastname' => function() {
                return 'lastname';
            },
            'getEmail' => function() {
                return 'mail@mail.de';
            },
            'getContextItem' => function() {
                $room = \Codeception\Stub::make(\cs_room_item::class, [
                    'getLanguage' => function() {
                        return 'de';
                    }
                ]);

                return $room;
            },
        ]);
        $I->assertFalse($moderatorWithoutMail->getOpenRoomWantMail());


        $moderators->add($moderatorWithMail);
        $moderators->add($moderatorWithoutMail);

        /** @var \cs_room_item $room */
        $room = \Codeception\Stub::make(\cs_room_item::class, [
            'itemId' => 123,
            '_moderator_list' => $moderators,
        ]);

        // Test without callback
        $recipients = RecipientFactory::createModerationRecipients($room);
        $I->assertCount(2, $recipients);

        // Test with optional closure
        $recipients = RecipientFactory::createModerationRecipients($room, function ($moderator) {
            return $moderator->getOpenRoomWantMail();
        });
        $I->assertCount(1, $recipients);
    }
}
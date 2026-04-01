<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Tests\Unit;

use App\Mail\Messages\ItemDeletedMessage;
use App\Mail\Recipient;
use App\Mail\RecipientFactory;
use Codeception\Stub;
use cs_list;
use cs_room_item;
use cs_user_item;
use Tests\Support\UnitTester;

class MailCest
{
    public function _before(UnitTester $I)
    {
    }

    public function _after(UnitTester $I)
    {
    }

    // tests
    public function invalidEmail(UnitTester $I): void
    {
        /** @var \App\Mail\Mailer $mailer */
        $mailer = $I->grabService(\App\Mail\Mailer::class);

        require_once 'classes/cs_room_item.php';
        /** @var cs_room_item $room */
        $room = Stub::make(cs_room_item::class, [
            'itemId' => 123,
        ]);

        $message = new \App\Mail\Messages\RoomArchivedMessage($room, 1122);

        $invalid = Stub::make(Recipient::class, [
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'invalidemail',
            'language' => 'de',
        ]);

        $valid = Stub::make(Recipient::class, [
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'valid@test.de',
            'language' => 'de',
        ]);

        $sendStatus = $mailer->send($message, $invalid, 'fromSender');
        $I->assertFalse($sendStatus->isSuccess());

        $sendStatus = $mailer->send($message, $valid, 'fromSender', ['invalidemail' => 'invalid']);
        $I->assertFalse($sendStatus->isSuccess());
    }

    public function validEmail(UnitTester $I): void
    {
        /** @var \App\Mail\Mailer $mailer */
        $mailer = $I->grabService(\App\Mail\Mailer::class);

        require_once 'classes/cs_room_item.php';
        /** @var cs_room_item $room */
        $room = Stub::make(cs_room_item::class, [
            'itemId' => 123,
        ]);

        $message = new \App\Mail\Messages\RoomArchivedMessage($room, 1122);

        $valid = Stub::make(Recipient::class, [
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'email' => 'valid@test.de',
            'language' => 'de',
        ]);

        $sendStatus = $mailer->send($message, $valid, 'fromSender', ['validemail@test.de' => 'name']);
        $I->assertTrue($sendStatus->isSuccess());
    }


    public function roomArchivedMessage(UnitTester $I): void
    {
        require_once 'classes/cs_room_item.php';
        /** @var cs_room_item $room */
        $room = Stub::make(cs_room_item::class, [
            'itemId' => 123,
        ]);

        $message = new \App\Mail\Messages\RoomArchivedMessage($room, 1122);

        $valid = Stub::make(Recipient::class, [
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

    public function itemDeletedMessage(UnitTester $I): void
    {
        require_once 'classes/cs_room_item.php';

        /** @var \cs_item $item */
        $item = Stub::make(\cs_item::class, [
            'itemId' => 123,
            'getContextItem' => fn() => Stub::make(cs_room_item::class, [
                'itemId' => 123,
                'title' => 'Room',
            ]),
        ]);

        $deleter = Stub::make(cs_user_item::class, [
            'getFirstname' => fn() => 'firstname',
            'getLastname' => fn() => 'lastname',
            'getEmail' => fn() => 'mail@mail.de',
            'getContextItem' => function() {
                $room = Stub::make(cs_room_item::class, [
                    'getLanguage' => fn() => 'de'
                ]);

                return $room;
            },
        ]);

        $recipient = Stub::make(Recipient::class, [
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

    public function recipientFactoryModerators(UnitTester $I): void
    {
        require_once 'classes/cs_room_item.php';
        $moderators = new cs_list();

        $moderatorWithMail = Stub::make(cs_user_item::class, [
            'getOpenRoomWantMail' => fn() => true,
            'getFirstname' => fn() => 'firstname',
            'getLastname' => fn() => 'lastname',
            'getEmail' => fn() => 'mail@mail.de',
            'getContextItem' => function() {
                $room = Stub::make(cs_room_item::class, [
                    'getLanguage' => fn() => 'de'
                ]);

                return $room;
            },
        ]);
        $I->assertTrue($moderatorWithMail->getOpenRoomWantMail());

        $moderatorWithoutMail = Stub::make(cs_user_item::class, [
            'getOpenRoomWantMail' => fn() => false,
            'getFirstname' => fn() => 'firstname',
            'getLastname' => fn() => 'lastname',
            'getEmail' => fn() => 'mail@mail.de',
            'getContextItem' => function() {
                $room = Stub::make(cs_room_item::class, [
                    'getLanguage' => fn() => 'de'
                ]);

                return $room;
            },
        ]);
        $I->assertFalse($moderatorWithoutMail->getOpenRoomWantMail());


        $moderators->add($moderatorWithMail);
        $moderators->add($moderatorWithoutMail);

        /** @var cs_room_item $room */
        $room = Stub::make(cs_room_item::class, [
            'itemId' => 123,
            '_moderator_list' => $moderators,
        ]);

        // Test without callback
        $recipients = RecipientFactory::createModerationRecipients($room);
        $I->assertCount(2, $recipients);

        // Test with optional closure
        $recipients = RecipientFactory::createModerationRecipients($room, fn($moderator) => $moderator->getOpenRoomWantMail());
        $I->assertCount(1, $recipients);
    }
}

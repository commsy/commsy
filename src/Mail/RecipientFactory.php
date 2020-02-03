<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-03-18
 * Time: 17:26
 */

namespace App\Mail;


class RecipientFactory
{
    public static function createModerationRecipients(\cs_room_item $room, callable $callback = null)
    {
        $recipients = [];

        $moderators = $room->getModeratorList();
        if (!$moderators->isEmpty()) {
            /** @var \cs_user_item $moderator */
            $moderator = $moderators->getFirst();

            while ($moderator) {
                if ($callback) {
                    if ($callback($moderator)) {
                        $recipients[] = RecipientFactory::createRecipient($moderator);
                    }
                } else {
                    $recipients[] = RecipientFactory::createRecipient($moderator);
                }

                $moderator = $moderators->getNext();
            }
        }

        return $recipients;
    }

    public static function createRecipient(\cs_user_item $user): Recipient
    {
        $recipient = new Recipient();
        $recipient->setFirstname($user->getFirstname());
        $recipient->setLastname($user->getLastname());
        $recipient->setEmail($user->getEmail());

        $room = $user->getContextItem();

        $language = $room->getLanguage();
        if ($language === 'user') {
            $language = $user->getLanguage();
            if ($language === 'browser') {
                // TODO: Get default language from parameters
                $language = 'de';
            }
        }

        $recipient->setLanguage($language);

        return $recipient;
    }
}
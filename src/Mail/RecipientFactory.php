<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 2019-03-18
 * Time: 17:26
 */

namespace App\Mail;


use App\Entity\Account;
use cs_room_item;
use cs_user_item;

class RecipientFactory
{
    /**
     * @param cs_room_item $room
     * @param callable|null $callback
     * @return array
     */
    public static function createModerationRecipients(cs_room_item $room, callable $callback = null): array
    {
        $recipients = [];

        $moderators = $room->getModeratorList();
        foreach ($moderators as $moderator) {
            /** @var cs_user_item $moderator */
            if ($callback) {
                if ($callback($moderator)) {
                    $recipients[] = self::createRecipient($moderator);
                }
            } else {
                $recipients[] = self::createRecipient($moderator);
            }
        }

        return $recipients;
    }

    /**
     * @param cs_user_item $user
     * @return Recipient
     */
    public static function createRecipient(cs_user_item $user): Recipient
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

    /**
     * @param string $email
     * @param string $firstname
     * @param string $lastname
     * @param string $language
     * @return Recipient
     */
    public static function createFromRaw(
        string $email,
        string $firstname = '',
        string $lastname = '',
        string $language = 'de'
    ): Recipient {
        $recipient = new Recipient();
        $recipient->setFirstname($firstname);
        $recipient->setLastname($lastname);
        $recipient->setEmail($email);
        $recipient->setLanguage($language);

        return $recipient;
    }

    /**
     * @param Account $account
     * @return Recipient
     */
    public static function createFromAccount(
        Account $account
    ): Recipient {
        $recipient = new Recipient();
        $recipient->setFirstname($account->getFirstname());
        $recipient->setLastname($account->getLastname());
        $recipient->setEmail($account->getEmail());
        $recipient->setLanguage($account->getLanguage());

        return $recipient;
    }
}
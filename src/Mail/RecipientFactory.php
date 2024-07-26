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

namespace App\Mail;

use App\Entity\Account;
use App\Proxy\PortalProxy;
use cs_room_item;
use cs_user_item;

class RecipientFactory
{
    public static function createModerationRecipients(
        cs_room_item|PortalProxy $context,
        callable $callback = null
    ): array
    {
        $recipients = [];

        $moderators = $context->getModeratorList();
        foreach ($moderators as $moderator) {
            /* @var cs_user_item $moderator */
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

    public static function createRecipients(cs_user_item ...$users): iterable
    {
        foreach ($users as $user) {
            yield self::createRecipient($user);
        }
    }

    public static function createRecipient(cs_user_item $user): Recipient
    {
        $recipient = new Recipient();
        $recipient->setFirstname($user->getFirstname());
        $recipient->setLastname($user->getLastname());
        $recipient->setEmail($user->getEmail());

        $room = $user->getContextItem();

        $language = $room->getLanguage();
        if ('user' === $language) {
            $language = $user->getLanguage();
            if ('browser' === $language) {
                // TODO: Get default language from parameters
                $language = 'de';
            }
        }

        $recipient->setLanguage($language);

        return $recipient;
    }

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

    public static function createFromAccounts(Account ...$accounts): iterable
    {
        foreach ($accounts as $account) {
            yield self::createFromAccount($account);
        }
    }
}

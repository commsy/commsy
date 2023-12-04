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

namespace App\Form\DataTransformer;

use cs_privateroom_item;

class PrivateRoomTransformer extends AbstractTransformer
{
    protected $entity = 'privateroom';

    /**
     * Transforms a cs_room_item object to an array.
     *
     * @param cs_privateroom_item $privateRoomItem
     */
    public function transform($privateRoomItem): array
    {
        $privateRoomData = [];
        if ($privateRoomItem) {
            $privateRoomData['newsletterStatus'] = $privateRoomItem->getPrivateRoomNewsletterActivity();
            if ('1' == $privateRoomItem->getCSBarShowWidgets()) {
                $privateRoomData['widgetStatus'] = true;
            } else {
                $privateRoomData['widgetStatus'] = false;
            }

            if ('1' == $privateRoomItem->getCSBarShowCalendar()) {
                $privateRoomData['calendarStatus'] = true;
            } else {
                $privateRoomData['calendarStatus'] = false;
            }

            if ('1' == $privateRoomItem->getCSBarShowStack()) {
                $privateRoomData['stackStatus'] = true;
            } else {
                $privateRoomData['stackStatus'] = false;
            }

            if ('1' == $privateRoomItem->getCSBarShowOldRoomSwitcher()) {
                $privateRoomData['switchRoomStatus'] = true;
            } else {
                $privateRoomData['switchRoomStatus'] = false;
            }

            if ('none' == $privateRoomItem->getPrivateRoomNewsletterActivity()) {
                $privateRoomData['newsletterStatus'] = '1';
            } elseif ('weekly' == $privateRoomItem->getPrivateRoomNewsletterActivity()) {
                $privateRoomData['newsletterStatus'] = '2';
            } elseif ('daily' == $privateRoomItem->getPrivateRoomNewsletterActivity()) {
                $privateRoomData['newsletterStatus'] = '3';
            }

            // Portfolio
            $privateRoomData['portfolio'] = $privateRoomItem->isPortfolioEnabled();

            // email to commsy
            $privateRoomData['emailToCommsy'] = $privateRoomItem->getEmailToCommSy();
            $privateRoomData['emailToCommsySecret'] = $privateRoomItem->getEmailToCommSySecret();
        }

        return $privateRoomData;
    }

    /**
     * Applies an array of data to an existing object.
     *
     * @param cs_privateroom_item $privateRoomObject
     * @param array $privateRoomData
     *
     */
    public function applyTransformation($privateRoomObject, $privateRoomData): cs_privateroom_item
    {
        if ($privateRoomObject) {
            if ('1' == $privateRoomData['widgetStatus']) {
                $privateRoomObject->setCSBarShowWidgets('1');
            } else {
                $privateRoomObject->setCSBarShowWidgets('-1');
            }

            if ('1' == $privateRoomData['calendarStatus']) {
                $privateRoomObject->setCSBarShowCalendar('1');
            } else {
                $privateRoomObject->setCSBarShowCalendar('-1');
            }

            if ('1' == $privateRoomData['stackStatus']) {
                $privateRoomObject->setCSBarShowStack('1');
            } else {
                $privateRoomObject->setCSBarShowStack('-1');
            }

            if ('1' == $privateRoomData['switchRoomStatus']) {
                $privateRoomObject->setCSBarShowOldRoomSwitcher('1');
            } else {
                $privateRoomObject->setCSBarShowOldRoomSwitcher('-1');
            }

            // TODO: Set language in portal / portalProxy
            if (isset($privateRoomData['language'])) {
                $privateRoomObject->setLanguage($privateRoomData['language']);
            }

            $set_to = 'none';
            if (isset($privateRoomData['newsletterStatus']) && !empty($privateRoomData['newsletterStatus'])) {
                if ('2' == $privateRoomData['newsletterStatus']) {
                    $set_to = 'weekly';
                } elseif ('3' == $privateRoomData['newsletterStatus']) {
                    $set_to = 'daily';
                }
            }
            $privateRoomObject->setPrivateRoomNewsletterActivity($set_to);

            // Portfolio
            $privateRoomObject->setPortfolioEnabled($privateRoomData['portfolio']);

            // email to commsy
            if (isset($privateRoomData['emailToCommsy'])) {
                if ('1' == $privateRoomData['emailToCommsy']) {
                    $privateRoomObject->setEmailToCommSy();
                } else {
                    $privateRoomObject->unsetEmailToCommSy();
                }
            }
            if (isset($privateRoomData['emailToCommsySecret'])) {
                $privateRoomObject->setEmailToCommSySecret($privateRoomData['emailToCommsySecret']);
            } else {
                $privateRoomObject->setEmailToCommSySecret('');
            }
        }

        return $privateRoomObject;
    }
}

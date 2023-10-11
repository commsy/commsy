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

namespace App\EventSubscriber;

use App\Utils\UserService;
use Spiriit\Bundle\FormFilterBundle\Event\GetFilterConditionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EventListener for use with SpiriitFormFilterBundle, which customizes
 * the doctrine conditions for the room membership filter.
 */
class RoomMembershipFilterConditionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    /**
     * Limits the room results to those the current user is member of.
     *
     * @param GetFilterConditionEvent $event the event
     */
    public function onGetFilterCondition(GetFilterConditionEvent $event)
    {
        $expr = $event->getFilterQuery()->getExpr();
        $values = $event->getValues();

        if (!empty($values['value'])) {
            $currentUserItem = $this->userService->getCurrentUserItem();
            $userRoomList = $this->userService->getRoomList($currentUserItem);

            $roomIds = [];
            foreach ($userRoomList as $room) {
                $roomIds[] = $room->getItemId();
            }

            if (!empty($roomIds)) {
                $event->setCondition(
                    $expr->in('r.itemId', $roomIds), []
                );
            } else {
                $event->setCondition(
                    $expr->isNull('r.itemId')
                );
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return ['lexik_form_filter.apply.orm.room_filter.membership' => 'onGetFilterCondition'];
    }
}

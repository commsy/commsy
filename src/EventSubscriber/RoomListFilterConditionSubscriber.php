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

use App\Utils\RoomService;
use App\Utils\UserService;
use Spiriit\Bundle\FormFilterBundle\Event\GetFilterConditionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EventListener for use with SpiriitFormFilterBundle, which customizes
 * the doctrine conditions for the room list filters.
 */
readonly class RoomListFilterConditionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserService $userService,
        private RoomService $roomService
    ) {
    }

    /**
     * Limits the room results to those the current user is member of.
     */
    public function onGetMembershipFilterCondition(GetFilterConditionEvent $event): void
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
     * Limits the room results to those matching the time pulses.
     */
    public function onGetTimePulsesFilterCondition(GetFilterConditionEvent $event): void
    {
        $expr = $event->getFilterQuery()->getExpr();
        $values = $event->getValues();

        if (!empty($values['value'])) {
            $value = $values['value'];

            if ('cont' === $value) {
                $event->setCondition(
                    $expr->eq('r.continuous', ':continuous'), [
                        'continuous' => '1',
                    ]
                );
            } else {
                $roomIds = $this->roomService->getRoomsInTimePulse($values['value']);

                if (!empty($roomIds)) {
                    $event->setCondition(
                        $expr->in('r.itemId', $roomIds),
                        []
                    );
                } else {
                    $event->setCondition(
                        $expr->isNull('r.itemId')
                    );
                }
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'spiriit_form_filter.apply.orm.room_filter.membership' => 'onGetMembershipFilterCondition',
            'spiriit_form_filter.apply.orm.room_filter.timePulses' => 'onGetTimePulsesFilterCondition',
        ];
    }
}

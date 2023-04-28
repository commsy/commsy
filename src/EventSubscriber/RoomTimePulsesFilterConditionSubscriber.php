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
use Lexik\Bundle\FormFilterBundle\Event\GetFilterConditionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * EventListener for use with LexikFormFilterBundle, which customizes
 * the doctrine conditions for the room time pulses filter.
 */
class RoomTimePulsesFilterConditionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly RoomService $roomService)
    {
    }

    /**
     * Limits the room results to those matching the time pulses.
     *
     * @param GetFilterConditionEvent $event the event
     */
    public function onGetFilterCondition(GetFilterConditionEvent $event)
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

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return ['lexik_form_filter.apply.orm.room_filter.timePulses' => 'onGetFilterCondition'];
    }
}

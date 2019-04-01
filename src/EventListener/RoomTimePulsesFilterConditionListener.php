<?php

namespace App\EventListener;

use Lexik\Bundle\FormFilterBundle\Event\GetFilterConditionEvent;

use App\Utils\RoomService;

/**
 * EventListener for use with LexikFormFilterBundle, which customizes
 * the doctrine conditions for the room time pulses filter
 */
class RoomTimePulsesFilterConditionListener
{
    private $roomService;

    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    /**
     * Limits the room results to those matching the time pulses.
     *
     * @param  GetFilterConditionEvent $event the event
     */
    public function onGetFilterCondition(GetFilterConditionEvent $event)
    {
        $expr = $event->getFilterQuery()->getExpr();
        $values = $event->getValues();

        if (!empty($values['value'])) {
            $value = $values['value'];

            if ($value === 'cont') {
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
}
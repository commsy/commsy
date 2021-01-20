<?php


namespace App\Search\FilterConditions;

use App\Entity\Room;
use Elastica\Query\Terms;

class MultipleRoomsFilterCondition implements FilterConditionInterface
{
    /**
     * @var Room[] $rooms
     */
    private $rooms;

    /**
     * @return Terms[]
     */
    public function getConditions(): array
    {
        $roomIds = [];
        foreach ($this->getRoomIds() as $key => $val) {
            $roomIds[] = $val;
        }

        $multipleRoomsFilter = new Terms();
        $multipleRoomsFilter->setTerms('contextId', $roomIds);

        return [$multipleRoomsFilter];
    }

    /**
     * @return string
     */
    public function getOperator(): string
    {
        return FilterConditionInterface::BOOL_MUST;
    }

    /**
     * @param Room[] $rooms
     */
    public function setRoomIds(array $rooms): void
    {
        $this->rooms = $rooms;
    }

    public function getRoomIds(): array
    {
        return $this->rooms ?? [];
    }


}
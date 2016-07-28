<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Utils\UserService;
use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class AppearanceSettingsTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService, UserService $userService)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;
    }

    /**
     * Transforms a cs_room_item object to an array
     *
     * @param cs_room_item $roomItem
     * @return array
     */
    public function transform($roomItem)
    {
        $roomData = array();

        if ($roomItem) {
            $roomData['dates_status'] = $roomItem->getDatesPresentationStatus();
            $roomData['theme'] = $roomItem->getColorArray()['schema'];
        }
        
        return $roomData;
    }

    /**
     * Augment given object "roomObject" with given array "roomData" and return the room object.
     *
     * @param object $roomObject
     * @param array $roomData
     * @return cs_room_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($roomObject, $roomData)
    {
        if ( isset($roomData['dates_status']) ) {
            $roomObject->setDatesPresentationStatus($roomData['dates_status']);
        }
        if( isset($roomData['theme']) ){
            $roomObject->setColorArray(array('schema' => $roomData['theme']));
        }
        return $roomObject;
    }
}

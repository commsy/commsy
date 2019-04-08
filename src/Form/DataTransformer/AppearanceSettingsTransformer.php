<?php
namespace App\Form\DataTransformer;

use App\Utils\RoomService;
use App\Utils\UserService;
use App\Services\LegacyEnvironment;
use App\Form\DataTransformer\DataTransformerInterface;

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
            // room image
            if($roomItem->getBGImageFilename()){
                $roomData['room_image']['choice'] = 'custom_image';
            }
            else{
                $roomData['room_image']['choice'] = 'default_image';
            }
            $roomData['room_logo']['activate'] = !empty($roomItem->getLogoFilename());
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

        // delete bg image
        /*
        if (isset($roomData['delete_custom_image']) && $roomData['delete_custom_image'] == '1') {
            $disc_manager = $this->legacyEnvironment->getDiscManager();

            if($disc_manager->existsFile($roomObject->getBGImageFilename())) {
                $disc_manager->unlinkFile($roomObject->getBGImageFilename());
            }

            $roomObject->setBGImageFilename('');
        }
        */

        // bg image repeat
        if (isset($roomData['room_image']['repeat_x']) && $roomData['room_image']['repeat_x'] == '1')
            $roomObject->setBGImageRepeat();
        else
            $roomObject->unsetBGImageRepeat();
            
        return $roomObject;
    }
}

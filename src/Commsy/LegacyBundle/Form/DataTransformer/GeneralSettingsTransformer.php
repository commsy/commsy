<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Utils\UserService;
use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class GeneralSettingsTransformer implements DataTransformerInterface
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

        $defaultRubrics = $roomItem->getAvailableDefaultRubricArray();

        if ($roomItem) {
            $roomData['title'] = $roomItem->getTitle();
            $roomData['language'] = $roomItem->getLanguage();

            if ($roomItem->checkNewMembersAlways()) {
                $roomData['access_check'] = 'always';
            } else if ($roomItem->checkNewMembersNever()) {
                $roomData['access_check'] = 'never';
            } else if ($roomItem->checkNewMembersWithCode()) {
                $roomData['access_check'] = 'withcode';
            }

            $backgroundImageFilename = $roomItem->getBGImageFilename();

            if($backgroundImageFilename){
                $roomData['room_image_choice'] = 'custom_image';
            }
            else{
                $roomData['room_image_choice'] = 'default_image';
            }

		    $roomData['room_image_repeat_x'] = $roomItem->issetBGImageRepeat();

            $roomData['room_description'] = $roomItem->getDescription();
            
            $roomData['wikiEnabled'] = $roomItem->isWikiEnabled();

            $rubrics = array_combine($defaultRubrics, array_fill(0, count($defaultRubrics), 'off'));
            foreach ($this->roomService->getRubricInformation($roomItem->getItemID(), true) as $rubric) {
                list($rubricName, $modifier) = explode('_', $rubric);
                $rubrics[$rubricName] = $modifier;
            }
            $roomData['rubrics'] = $rubrics;
        }
        return $roomData;
    }

  /**
     * Save general settings
     *
     * @param object $roomObject
     * @param array $roomData
     * @return cs_room_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($roomObject, $roomData)
    {
        $rubricArray = array();
        foreach ($roomData['rubrics'] as $rubricName => $rubricValue) { 
            if (strcmp($rubricValue, 'off') == 0) {
                continue;
            }
            $rubricArray[] = $rubricName . "_" . $rubricValue;
        }

        $roomObject->setHomeConf(implode($rubricArray, ','));

        $roomObject->setTitle($roomData['title']);

        if (isset($roomData['language'])) {
            $roomObject->setLanguage($roomData['language']);
        }

        if (isset($roomData['wikiEnabled'])) {
            $roomObject->setWikiEnabled($roomData['wikiEnabled']);
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

        // bg image repeat
        if (isset($form['room_image_repeat_x']) && $form['room_image_repeat_x'] == '1')
            $roomObject->setBGImageRepeat();
        else
            $roomObject->unsetBGImageRepeat();
        */

        if(isset($roomData['room_description'])) {
            $roomObject->setDescription(strip_tags($roomData['room_description']));
        }
        else 
            $roomObject->setDescription('');

        // assignment
        if($roomObject->isProjectRoom()) {
            $community_room_array = array();

            // get community room ids
            foreach($roomData as $key => $value) {
                if(mb_substr($key, 0, 18) === 'communityroomlist_') $community_room_array[] = $value;
            }
            
            /*
             * if assignment is mandatory, the array must not be empty
             */
            if ($this->legacyEnvironment->getCurrentPortalItem()->getProjectRoomLinkStatus() !== "mandatory" || sizeof($community_room_array) > 0 )
            {
                $roomObject->setCommunityListByID($community_room_array);
            }

        } elseif($roomObject->isCommunityRoom()) {
            if(isset($roomData['room_assignment'])) {
                if($roomData['room_assignment'] === 'open') $roomObject->setAssignmentOpenForAnybody();
                elseif($roomData['room_assignment'] === 'closed') $roomObject->setAssignmentOnlyOpenForRoomMembers();
            }
        }

        // check member
        if (isset($roomData['member_check'])) {
            switch($roomData['member_check']) {
                case "never":
                    $userService->grantAccessToAllPendingApplications();
                    $roomObject->setCheckNewMemberNever();
                    break;
                case "always":
                    $roomObject->setCheckNewMemberAlways();
                    break;
                case "withcode":
                    $roomObject->setCheckNewMemberWithCode();
                    // TODO: add 'code' field to form!
                    //$roomObject->setCheckNewMemberCode($roomData['code']);
                    break;
            }
        }
        return $roomObject;
    }
}

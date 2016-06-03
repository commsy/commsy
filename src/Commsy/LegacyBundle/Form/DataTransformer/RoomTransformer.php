<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Utils\UserService;
use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class RoomTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService = null)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;

        $this->rubricsMapping = array(
            'Announcements' => 'announcement',
            'Events' => 'date',
            'Materials' => 'material',
            'Discussions' => 'discussion',
            'Members' => 'user',
            'Groups' => 'group',
            'Tasks' => 'todo',
            'Topics' => 'topic',
        );
        $this->rubricsMappingFlipped = array_flip($this->rubricsMapping);
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
            $roomData['title'] = $roomItem->getTitle();
            $roomData['language'] = $roomItem->getLanguage();

            if ($roomItem->checkNewMembersAlways()) {
                $roomData['access_check'] = 'always';
            } else if ($roomItem->checkNewMembersNever()) {
                $roomData['access_check'] = 'never';
            } else if ($roomItem->checkNewMembersWithCode()) {
                $roomData['access_check'] = 'withcode';
            }

            $roomData['description'] = $roomItem->getDescription();
            
            $roomData['wikiEnabled'] = $roomItem->isWikiEnabled();

            // TODO clean this messs up! 
            $rubricsArray = array_combine(array_values($this->rubricsMapping), array_values(array_fill(0, count($this->rubricsMapping), 'off')));
            $rubrics = $this->roomService->getRubricInformation($roomItem->getItemID(), true);
            foreach ($rubrics as $rubric) {
                list($rubricName, $modifier) = explode('_', $rubric);
                $rubricsArray[$rubricName] = $modifier;
            }
            $rubricsArrayTransformed = array();
            foreach ($rubricsArray as $rubricName => $rubricModifier) {
                $rubricsArrayTransformed[$this->rubricsMappingFlipped[$rubricName]] = $rubricModifier;
            }
            $roomData['rubrics'] = $rubricsArrayTransformed;
        }
        return $roomData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $roomObject
     * @param array $roomData
     * @return cs_room_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($roomObject, $roomData)
    {

        $rubricData = $roomData['rubrics'];
        $roomRubrics = array_combine(array_map(function($val){return $this->rubricsMapping[$val];}, array_keys($rubricData)), array_values($rubricData));
        $tempArray = array();
        foreach ($roomRubrics as $rubricName => $rubricValue) {
            if (strcmp($rubricValue, 'off') == 0) {
                continue;
            }
            $tempArray[] = $rubricName . "_" . $rubricValue;
        }
        $roomObject->setHomeConf(implode($tempArray, ','));

        if (isset($roomData['title']) ){
            $roomObject->setTitle($roomData['title']);
        }

        if (isset($roomData['language']) ){
            $roomObject->setLanguage($roomData['language']);
        }

        if ( isset($roomData['wikiEnabled']) ){
            $roomObject->setWikiEnabled($roomData['wikiEnabled']);
        }

        // check member
        if ( isset($roomData['member_check']) ) {
            switch($roomData['member_check']) {
                case "never":
                    $userService = $this->get('commsy.user_service');
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

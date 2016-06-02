<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Utils\RoomService;
use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class RoomTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment, RoomService $roomService = null)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->roomService = $roomService;

        $this->rubrics = array(
            'Announcements' => 'announcement',
            'Events' => 'date',
            'Materials' => 'material',
            'Discussions' => 'discussion',
            'Members' => 'user',
            'Groups' => 'group',
            'Tasks' => 'todo',
            'Topics' => 'topic',
        );
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
            } else if ($roomItem->checkNewMembersSometimes()) {
                $roomData['access_check'] = 'sometimes';
            } else if ($roomItem->checkNewMembersWithCode()) {
                $roomData['access_check'] = 'withcode';
            }

            $roomData['description'] = $roomItem->getDescription();
            
            $roomData['wikiEnabled'] = $roomItem->isWikiEnabled();

            $rubricsArray = array_combine(array_values($this->rubrics), array_values(array_fill(0, count($this->rubrics), 'hide')));
            $rubrics = $this->roomService->getRubricInformation($roomItem->getItemID(), true);
            foreach ($rubrics as $rubric) {
                list($rubricName, $modifier) = explode('_', $rubric);
                $rubricsArray[$rubricName] = $modifier;
            }
            $roomData['rubrics'] = $rubricsArray;
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
	// TODO: Daten in roomObject schreiben (an cs_popup_configuration_controller und cs_room_item (bzw. dessen Eltern-Klassen) orientieren!)	

        return $roomObject;
    }
}

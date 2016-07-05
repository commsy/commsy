<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class GroupTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_group_item object to an array
     *
     * @param cs_group_item $groupItem
     * @return array
     */
    public function transform($groupItem)
    {
        $groupData = array();

        if ($groupItem) {
            $groupData['title'] = $groupItem->getTitle();
            $groupData['description'] = $groupItem->getDescription();
            $groupData['permission'] = $groupItem->isPrivateEditing();
        }

        return $groupData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $groupObject
     * @param array $groupData
     * @return cs_group_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($groupObject, $groupData)
    {
        $groupObject->setTitle($groupData['title']);
        $groupObject->setDescription($groupData['description']);
        
        if ($groupData['permission']) {
            $groupObject->setPrivateEditing('0');
        } else {
            $groupObject->setPrivateEditing('1');
        }

        return $groupObject;
    }
}
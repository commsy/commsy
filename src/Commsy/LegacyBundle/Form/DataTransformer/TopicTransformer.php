<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class TopicTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_topic_item object to an array
     *
     * @param cs_topic_item $dateItem
     * @return array
     */
    public function transform($topicItem)
    {
        $topicData = array();

        if ($topicItem) {
            $topicData['title'] = $topicItem->getTitle();
            $topicData['description'] = $topicItem->getDescription();
            $topicData['permission'] = $topicItem->isPrivateEditing();
        }

        return $topicData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $topicObject
     * @param array $topicData
     * @return cs_topic_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($topicObject, $topicData)
    {
        $topicObject->setTitle($topicData['title']);
        $topicObject->setDescription($topicData['description']);
        
        if ($topicData['permission']) {
            $topicObject->setPrivateEditing('0');
        } else {
            $topicObject->setPrivateEditing('1');
        }

        return $topicObject;
    }
}
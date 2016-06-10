<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class DiscussionTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_discussion_item object to an array
     *
     * @param cs_discussion_item $discussionItem
     * @return array
     */
    public function transform($discussionItem)
    {
        $discussionData = array();

        if ($discussionItem) {
            $discussionData['title'] = $discussionItem->getTitle();
            $discussionData['permission'] = $discussionItem->isPrivateEditing();
        }

        return $discussionData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $discussionObject
     * @param array $discussionData
     * @return cs_discussion_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($discussionObject, $discussionData)
    {
        $discussionObject->setTitle($discussionData['title']);
        
        if ($discussionData['permission']) {
            $discussionObject->setPrivateEditing('0');
        } else {
            $discussionObject->setPrivateEditing('1');
        }

        return $discussionObject;
    }
}
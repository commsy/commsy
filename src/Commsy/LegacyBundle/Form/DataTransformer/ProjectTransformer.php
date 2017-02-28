<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class ProjectTransformer implements DataTransformerInterface
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
    public function transform($projectItem)
    {
        $projectData = [];

        if ($projectItem) {
            $projectData['title'] = $projectItem->getTitle();
            $projectData['description'] = $projectItem->getDescription();
        }

        return $projectData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $topicObject
     * @param array $topicData
     * @return cs_topic_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($projectObject, $projectData)
    {
        $projectObject->setTitle($projectData['title']);
        $projectObject->setDescription($projectData['description']);

        return $projectObject;
    }
}
<?php
namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use App\Form\DataTransformer\DataTransformerInterface;

class ProjectTransformer extends AbstractTransformer
{
    protected $entity = 'project';

    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_project_item object to an array
     *
     * @param \cs_project_item $projectItem
     * @return array
     */
    public function transform($projectItem)
    {
        $projectData = [];

        if ($projectItem) {
            $projectData['title'] = html_entity_decode($projectItem->getTitle());
            $projectData['description'] = $projectItem->getDescription();
        }

        return $projectData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $projectObject
     * @param array $projectData
     * @return \cs_project_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($projectObject, $projectData)
    {
        $projectObject->setTitle($projectData['title']);
        $projectObject->setDescription($projectData['description']);

        return $projectObject;
    }
}
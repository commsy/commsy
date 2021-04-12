<?php
namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use App\Form\DataTransformer\DataTransformerInterface;

class UserroomTransformer extends AbstractTransformer
{
    protected $entity = 'userroom';

    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_userroom_item object to an array
     *
     * @param \cs_userroom_item $userroom
     * @return array
     */
    public function transform($userroom)
    {
        $projectData = [];

        if ($userroom) {
            $projectData['title'] = html_entity_decode($userroom->getTitle());
            $projectData['description'] = $userroom->getDescription();
        }

        return $projectData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $userroomObject
     * @param array $userroomData
     * @return \cs_userroom_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($userroomObject, $userroomData)
    {
        $userroomObject->setTitle($userroomData['title']);
        $userroomObject->setDescription($userroomData['description']);

        return $userroomObject;
    }
}

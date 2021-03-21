<?php
namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use App\Form\DataTransformer\DataTransformerInterface;

class DiscussionarticleTransformer extends AbstractTransformer
{
    protected $entity = 'discarticle';

    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_discussion_item object to an array
     *
     * @param cs_discarticle_item $discussionarticleItem
     * @return array
     */
    public function transform($discussionarticleItem)
    {
        $discussionarticleData = array();

        if ($discussionarticleItem) {
            $discussionarticleData['title'] = html_entity_decode($discussionarticleItem->getTitle());
            $discussionarticleData['permission'] = $discussionarticleItem->isPrivateEditing();
            $discussionarticleData['description'] = $discussionarticleItem->getDescription();
        }

        return $discussionarticleData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $discussionObject
     * @param array $discussionData
     * @return cs_discussion_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($discussionarticleObject, $discussionarticleData)
    {
        $discussionarticleObject->setTitle($discussionarticleData['title']);
        
        if ($discussionarticleData['permission']) {
            $discussionarticleObject->setPrivateEditing('0');
        } else {
            $discussionarticleObject->setPrivateEditing('1');
        }

        $discussionarticleObject->setDescription($discussionarticleData['description']);

        return $discussionarticleObject;
    }
}
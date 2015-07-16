<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use CommSy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class MaterialTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_material_item object to an array
     *
     * @param cs_material_item $materialItem
     * @return array
     */
    public function transform($materialItem)
    {
        $materialData = array();

        if ($materialItem) {
            $materialData['title'] = $materialItem->getTitle();
            // $materialData['language'] = $materialItem->getLanguage();

            // if ($materialItem->checkNewMembersAlways()) {
            //     $materialData['access_check'] = 'always';
            // } else if ($materialItem->checkNewMembersNever()) {
            //     $materialData['access_check'] = 'never';
            // } else if ($materialItem->checkNewMembersSometimes()) {
            //     $materialData['access_check'] = 'sometimes';
            // } else if ($materialItem->checkNewMembersWithCode()) {
            //     $materialData['access_check'] = 'withcode';
            // }

            $materialData['description'] = $materialItem->getDescription();
        }

        return $materialData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $materialObject
     * @param array $materialData
     * @return cs_material_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($materialObject, $materialData)
    {
        $materialObject->setTitle($materialData['title']);
        $materialObject->setDescription($materialData['description']);
        
        return $materialObject;
    }
}
<?php
namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use App\Form\DataTransformer\DataTransformerInterface;

class AnnotationTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_material_item object to an array
     *
     * @param cs_material_item $annotationItem
     * @return array
     */
    public function transform($annotationItem)
    {
        $annotationData = array();

        if ($annotationItem) {
            $annotationData['description'] = $annotationItem->getDescription();
        }

        return $annotationData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $materialObject
     * @param array $annotationData
     * @return cs_material_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($annotationObject, $annotationData)
    {
        $annotationObject->setDescription($annotationData['description']);
        
        return $annotationObject;
    }
}
<?php
namespace App\Form\DataTransformer;

use cs_item;

class AnnotationTransformer extends AbstractTransformer
{
    protected $entity = 'annotation';

    /**
     * Transforms a \cs_item object to an array
     *
     * @param cs_item $annotationItem
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
     * @return cs_item|null
     */
    public function applyTransformation($annotationObject, $annotationData)
    {
        $annotationObject->setDescription($annotationData['description']);
        
        return $annotationObject;
    }
}
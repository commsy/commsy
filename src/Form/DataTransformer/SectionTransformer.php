<?php
namespace App\Form\DataTransformer;

use cs_section_item;

class SectionTransformer  extends AbstractTransformer
{
    protected $entity = 'section';

    /**
     * Transforms a cs_material_item object to an array
     *
     * @param cs_section_item $sectionItem
     * @return array
     */
    public function transform($sectionItem)
    {
        $sectionData = array();

        if ($sectionItem) {
            $sectionData['title'] = html_entity_decode($sectionItem->getTitle());
            $sectionData['description'] = $sectionItem->getDescription();
            $sectionData['permission'] = $sectionItem->isPrivateEditing();
        }

        return $sectionData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $sectionObject
     * @param array $sectionData
     * @return cs_section_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($sectionObject, $sectionData)
    {
        $sectionObject->setTitle($sectionData['title']);
        $sectionObject->setDescription($sectionData['description']);

        if ($sectionData['permission']) {
            $sectionObject->setPrivateEditing('0');
        } else {
            $sectionObject->setPrivateEditing('1');
        }

        return $sectionObject;
    }
}
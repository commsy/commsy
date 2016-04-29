<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class DateTimeTransformer implements DataTransformerInterface
{

    public function __construct()
    {
    }

    /**
     * Transforms a cs_material_item object to an array
     *
     * @param cs_material_item $announcementItem
     * @return array
     */
    public function transform($announcementItem)
    {
        $announcementData = array();

        if ($announcementItem) {
            $announcementData['title'] = $announcementItem->getTitle();
            $announcementData['description'] = $announcementItem->getDescription();
        }

        return $announcementData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $materialObject
     * @param array $announcementData
     * @return cs_material_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($announcementObject, $announcementData)
    {
        var_dump("test");
        
        return $announcementObject;
    }
}
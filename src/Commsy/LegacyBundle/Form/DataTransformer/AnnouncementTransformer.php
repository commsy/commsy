<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class AnnouncementTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
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

            $datetime = new \DateTime($announcementItem->getSecondDateTime());
            $announcementData['validdate']['date'] = $datetime;
            $announcementData['validdate']['time'] = $datetime;
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
        $announcementObject->setTitle($announcementData['title']);
        $announcementObject->setDescription($announcementData['description']);

        if ($announcementData['validdate']['date'] && $announcementData['validdate']['time']) {
            // add validdate to validdate
            $datetime = $announcementData['validdate']['date'];
            $time = explode(":", $announcementData['validdate']['time']->format('H:i'));
            $datetime->setTime($time[0], $time[1]);

            $announcementObject->setSecondDateTime($datetime->format('Y-m-d H:i:s'));

        } else {
            $datetime = $announcementData['validdate']['date'];
        }
        
        return $announcementObject;
    }
}
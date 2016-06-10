<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class DateTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_date_item object to an array
     *
     * @param cs_date_item $dateItem
     * @return array
     */
    public function transform($dateItem)
    {
        $dateData = array();

        if ($dateItem) {
            $dateData['title'] = $dateItem->getTitle();
            $dateData['description'] = $dateItem->getDescription();
            $dateData['permission'] = $dateItem->isPrivateEditing();
        }

        return $dateData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $dateObject
     * @param array $dateData
     * @return cs_date_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($dateObject, $dateData)
    {
        $dateObject->setTitle($dateData['title']);
        $dateObject->setDescription($dateData['description']);
        
        if ($dateData['permission']) {
            $dateObject->setPrivateEditing('0');
        } else {
            $dateObject->setPrivateEditing('1');
        }

        return $dateObject;
    }
}
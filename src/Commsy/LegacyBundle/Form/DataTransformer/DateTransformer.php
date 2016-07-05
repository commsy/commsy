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
            $dateData['place'] = $dateItem->getPlace();
            
            $datetimeStart = new \DateTime($dateItem->getDateTime_start());
            $dateData['start']['date'] = $datetimeStart;
            $dateData['start']['time'] = $datetimeStart;
            
            $datetimeEnd = new \DateTime($dateItem->getDateTime_start());
            $dateData['end']['date'] = $datetimeEnd;
            $dateData['end']['time'] = $datetimeEnd;
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
        error_log(print_r($dateData, true));
        
        $dateObject->setTitle($dateData['title']);
        $dateObject->setDescription($dateData['description']);
        
        if ($dateData['permission']) {
            $dateObject->setPrivateEditing('0');
        } else {
            $dateObject->setPrivateEditing('1');
        }

        $dateObject->setPlace($dateData['place']);
        
        $dateObject->setStartingDay($dateData['start']['date']->format('Y-m-d'));
        $dateObject->setStartingTime($dateData['start']['time']->format('H:i'));
        
        $dateObject->setEndingDay($dateData['end']['date']->format('Y-m-d'));
        $dateObject->setEndingTime($dateData['end']['time']->format('H:i'));

        return $dateObject;
    }
}
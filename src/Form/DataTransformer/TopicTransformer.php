<?php
namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use App\Form\DataTransformer\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TopicTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_topic_item object to an array
     *
     * @param cs_topic_item $dateItem
     * @return array
     */
    public function transform($topicItem)
    {
        $topicData = array();

        if ($topicItem) {
            $topicData['title'] = html_entity_decode($topicItem->getTitle());
            $topicData['description'] = $topicItem->getDescription();
            $topicData['permission'] = $topicItem->isPrivateEditing();
            
            if ($topicItem->isNotActivated()) {
                $topicData['hidden'] = true;
                
                $activating_date = $topicItem->getActivatingDate();
                if (!stristr($activating_date,'9999')){
                    $datetime = new \DateTime($activating_date);
                    $topicData['hiddendate']['date'] = $datetime;
                    $topicData['hiddendate']['time'] = $datetime;
                }
            }
        }

        return $topicData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $topicObject
     * @param array $topicData
     * @return \cs_topic_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($topicObject, $topicData)
    {
        $topicObject->setTitle($topicData['title']);
        $topicObject->setDescription($topicData['description']);
        
        if ($topicData['permission']) {
            $topicObject->setPrivateEditing('0');
        } else {
            $topicObject->setPrivateEditing('1');
        }

        if (isset($topicData['hidden'])) {
            if ($topicData['hidden']) {
                if ($topicData['hiddendate']['date']) {
                    // add validdate to validdate
                    $datetime = $topicData['hiddendate']['date'];
                    if ($topicData['hiddendate']['time']) {
                        $time = explode(":", $topicData['hiddendate']['time']->format('H:i'));
                        $datetime->setTime($time[0], $time[1]);
                    }
                    $topicObject->setModificationDate($datetime->format('Y-m-d H:i:s'));
                } else {
                    $topicObject->setModificationDate('9999-00-00 00:00:00');
                }
            } else {
                if($topicObject->isNotActivated()){
    	            $topicObject->setModificationDate(getCurrentDateTimeInMySQL());
    	        }
            }
        } else {
            if($topicObject->isNotActivated()){
	            $topicObject->setModificationDate(getCurrentDateTimeInMySQL());
	        }
        }

        return $topicObject;
    }
}
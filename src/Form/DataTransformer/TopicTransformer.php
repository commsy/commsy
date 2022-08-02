<?php
namespace App\Form\DataTransformer;

use DateTime;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TopicTransformer extends AbstractTransformer
{
    protected $entity = 'topic';

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
                    $datetime = new DateTime($activating_date);
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
                if (isset($topicData['hiddendate']['date'])) {
                    // add validdate to validdate
                    // TODO: the date-object ought to resepct the chosen system language
                    $datetime = $topicData['hiddendate']['date'];
                    if ($topicData['hiddendate']['time']) {
                        $time = explode(":", $topicData['hiddendate']['time']->format('H:i'));
                        $datetime->setTime($time[0], $time[1]);
                    }
                    $topicObject->setActivationDate($datetime->format('Y-m-d H:i:s'));
                } else {
                    $topicObject->setActivationDate('9999-00-00 00:00:00');
                }
            } else {
                if ($topicObject->isNotActivated()) {
    	            $topicObject->setActivationDate(null);
    	        }
            }
        } else {
            if ($topicObject->isNotActivated()) {
	            $topicObject->setActivationDate(null);
	        }
        }

        return $topicObject;
    }
}
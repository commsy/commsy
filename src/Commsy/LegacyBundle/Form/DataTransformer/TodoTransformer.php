<?php
namespace Commsy\LegacyBundle\Form\DataTransformer;

use Commsy\LegacyBundle\Services\LegacyEnvironment;
use Commsy\LegacyBundle\Form\DataTransformer\DataTransformerInterface;

class TodoTransformer implements DataTransformerInterface
{
    private $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_todo_item object to an array
     *
     * @param cs_todo_item $dateItem
     * @return array
     */
    public function transform($todoItem)
    {
        $todoData = array();

        if ($todoItem) {
            $todoData['title'] = $todoItem->getTitle();
            $todoData['description'] = $todoItem->getDescription();
            $todoData['permission'] = $todoItem->isPrivateEditing();
            
            if ($todoItem->isNotActivated()) {
                $todoData['hidden'] = true;
                
                $activating_date = $todoItem->getActivatingDate();
                if (!stristr($activating_date,'9999')){
                    $datetime = new \DateTime($activating_date);
                    $todoData['hiddendate']['date'] = $datetime;
                    $todoData['hiddendate']['time'] = $datetime;
                }
            }
            
            if ($todoItem->getDate()) {
                if ($todoItem->getDate() != '9999-00-00 00:00:00') {
                    $datetimeDueDate = new \DateTime($todoItem->getDate());
                    $todoData['due_date']['date'] = $datetimeDueDate;
                    $todoData['due_date']['time'] = $datetimeDueDate;
                }
            }
            
            $todoData['time_planned'] = $todoItem->getPlannedTime();
            
            $todoData['time_type'] = $todoItem->getTimeType();
            
            $todoData['status'] = $todoItem->getInternalStatus();
        }

        return $todoData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param object $todoObject
     * @param array $todoData
     * @return cs_todo_item|null
     * @throws TransformationFailedException if room item is not found.
     */
    public function applyTransformation($todoObject, $todoData)
    {
        if(isset($todoData['title'])){
            $todoObject->setTitle($todoData['title']);
        }
        if(isset($todoData['description'])){
            $todoObject->setDescription($todoData['description']);
        }
        
        if(isset($todoData['permission'])){
            if ($todoData['permission']) {
                $todoObject->setPrivateEditing('0');
            } else {
                $todoObject->setPrivateEditing('1');
            }
        }

        if (isset($todoData['hidden'])) {
            if ($todoData['hidden']) {
                if ($todoData['hiddendate']['date']) {
                    // add validdate to validdate
                    $datetime = $todoData['hiddendate']['date'];
                    if ($todoData['hiddendate']['time']) {
                        $time = explode(":", $todoData['hiddendate']['time']->format('H:i'));
                        $datetime->setTime($time[0], $time[1]);
                    }
                    $todoObject->setModificationDate($datetime->format('Y-m-d H:i:s'));
                } else {
                    $todoObject->setModificationDate('9999-00-00 00:00:00');
                }
            } else {
                if($todoObject->isNotActivated()){
    	            $todoObject->setModificationDate(getCurrentDateTimeInMySQL());
    	        }
            }
        } else {
            if($todoObject->isNotActivated()){
	            $todoObject->setModificationDate(getCurrentDateTimeInMySQL());
	        }
        }

        if (isset($todoData['due_date'])) {
            $todoObject->setDate($todoData['due_date']['date']->format('Y-m-d').' '.$todoData['due_date']['time']->format('H:i:s'));
        }

        if (isset($todoData['time_planned'])){
            $todoObject->setPlannedTime($todoData['time_planned']);
        }
        
        if (isset($todoData['time_type'])){
            $todoObject->setTimeType($todoData['time_type']);
        }

        if (isset($todoData['status'])){
            $todoObject->setStatus($todoData['status']);
        }

        return $todoObject;
    }
}
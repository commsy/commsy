<?php
namespace App\Form\DataTransformer;

use App\Services\LegacyEnvironment;
use cs_environment;
use DateTime;

class TodoTransformer extends AbstractTransformer
{
    protected $entity = 'todo';

    /**
     * @var cs_environment
     */
    private cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
    }

    /**
     * Transforms a cs_todo_item object to an array
     *
     * @param \cs_todo_item $dateItem
     * @return array
     */
    public function transform($todoItem)
    {
        $todoData = array();

        if ($todoItem) {
            $todoData['title'] = html_entity_decode($todoItem->getTitle());
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
            
            if (get_class($todoItem) != 'cs_step_item') {
                if ($todoItem->getDate() && $todoItem->getDate() != '9999-00-00 00:00:00') {
                    $datetimeDueDate = new \DateTime($todoItem->getDate());
                } else{
                    $datetimeDueDate = new \DateTime();
                }
                $todoData['due_date']['date'] = $datetimeDueDate;
                $todoData['due_date']['time'] = $datetimeDueDate;

                // $this->legacyEnvironment->getCurrentContextItem()->getLanguage()

                $todoData['steps'] = array();
                foreach($todoItem->getStepItemList()->to_array() as $id => $item){
                    $todoData['steps'][$id] = $item->getTitle();
                }

                $todoData['time_planned'] = $todoItem->getPlannedTime();

                $todoData['time_type'] = $todoItem->getTimeType();

                $todoData['status'] = $todoItem->getInternalStatus();
            } else {
                $minutes = (int) $todoItem->getMinutes();
                $todoData['time_spend'] = [];
                $todoData['time_spend']['hour'] = (int) ($minutes / 60);
                $todoData['time_spend']['minute'] = $minutes % 60;
            }

            // external viewer
            if ($this->legacyEnvironment->getCurrentContextItem()->isPrivateRoom()) {
                $todoData['external_viewer_enabled'] = true;
                $todoData['external_viewer'] = $todoItem->getExternalViewerString();
            } else {
                $todoData['external_viewer_enabled'] = false;
            }
        }

        return $todoData;
    }

    /**
     * Applies an array of data to an existing object
     *
     * @param \cs_todo_item $todoObject
     * @param array $todoData
     * @return \cs_todo_item
     */
    public function applyTransformation($todoObject, $todoData)
    {
        $todoObject->setTitle($todoData['title']);
        $todoObject->setDescription($todoData['description']);

        if(isset($todoData['permission'])){
            if ($todoData['permission']) {
                $todoObject->setPrivateEditing('0');
            } else {
                $todoObject->setPrivateEditing('1');
            }
        }

        if (isset($todoData['hidden'])) {
            if ($todoData['hidden']) {
                if (isset($todoData['hiddendate']['date'])) {
                    // add validdate to validdate
                    $datetime = $todoData['hiddendate']['date'];
                    if ($todoData['hiddendate']['time']) {
                        $time = explode(":", $todoData['hiddendate']['time']->format('H:i'));
                        $datetime->setTime($time[0], $time[1]);
                    }
                    $todoObject->setActivationDate($datetime->format('Y-m-d H:i:s'));
                } else {
                    $todoObject->setActivationDate('9999-00-00 00:00:00');
                }
            } else {
                if($todoObject->isNotActivated()){
    	            $todoObject->setActivationDate(new DateTime());
    	        }
            }
        } else {
            if($todoObject->isNotActivated()){
	            $todoObject->setActivationDate(new DateTime());
	        }
        }

        if (get_class($todoObject) != 'cs_step_item') {
            if (isset($todoData['time_planned'])){
                $todoObject->setPlannedTime($todoData['time_planned']);
            }

            if (isset($todoData['time_type'])){
                $todoObject->setTimeType($todoData['time_type']);
            }

            if (isset($todoData['status'])){
                $todoObject->setStatus($todoData['status']);
            }

            // steps
            if(isset($todoData['stepOrder'])){
                $newStepOrder = explode(",", $todoData['stepOrder']);
            }

            if (isset($todoData['due_date']['date'])) {
                $todoObject->setDate($todoData['due_date']['date']->format('Y-m-d').' '.$todoData['due_date']['time']->format('H:i:s'));
            }
        } else {
            $hours = is_numeric($todoData['time_spend']['hour']) ? $todoData['time_spend']['hour'] : 0;
            $minutes = is_numeric($todoData['time_spend']['minute']) ? $todoData['time_spend']['minute'] : 0;
            $todoObject->setMinutes($hours * 60 + $minutes);
        }

        // external viewer
        if ($this->legacyEnvironment->getCurrentContextItem()->isPrivateRoom()) {
            if (!empty(trim($todoData['external_viewer']))) {
                $userIds = explode(" ", $todoData['external_viewer']);
                $todoObject->setExternalViewerAccounts($userIds);
            } else {
                $todoObject->unsetExternalViewerAccounts();
            }
        }

        return $todoObject;
    }
}
<?php

namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class TopicService
{
    private $legacyEnvironment;

    private $topicManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;
        
        $this->topicManager = $this->legacyEnvironment->getEnvironment()->getTopicManager();
        $this->topicManager->reset();
    }


    public function getCountArray($roomId)
    {
        $this->topicManager->setContextLimit($roomId);
        $this->topicManager->select();
        $countTopic = array();
        $countTopicArray['count'] = sizeof($this->topicManager->get()->to_array());
        $this->topicManager->select();
        $countTopicArray['countAll'] = $this->topicManager->getCountAll();

        return $countTopicArray;
    }


    public function getTopic($itemId)
    {
        $topic = $this->topicManager->getItem($itemId);
        return $topic;
    }
    
    public function getListTopics($roomId, $max = NULL, $start = NULL)
    {
        $this->topicManager->setContextLimit($roomId);
        if ($max !== NULL && $start !== NULL) {
            $this->topicManager->setIntervalLimit($start, $max);
        }

        $this->topicManager->select();
        $topicList = $this->topicManager->get();

        return $topicList->to_array();
    }
    
    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['activated']) {
            $this->topicManager->showNoNotActivatedEntries();
        }
    }
}
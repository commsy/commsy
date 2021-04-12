<?php

namespace App\Utils;

use Symfony\Component\Form\Form;

use App\Services\LegacyEnvironment;
use Symfony\Component\Form\FormInterface;

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
        $countTopicArray['count'] = sizeof($this->topicManager->get()->to_array());
        $this->topicManager->resetLimits();
        $this->topicManager->select();
        $countTopicArray['countAll'] = $this->topicManager->getCountAll();

        return $countTopicArray;
    }

    /**
     * @param int $itemId
     * @return \cs_topic_item
     */
    public function getTopic($itemId): \cs_topic_item
    {
        /** @var \cs_topic_item $topic */
        $topic = $this->topicManager->getItem($itemId);
        return $topic;
    }

    /**
     * @param integer $roomId
     * @param integer $max
     * @param integer $start
     * @return \cs_topic_item[]
     */
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

    /**
     * @param integer $roomId
     * @param integer[] $ids
     * @return \cs_topic_item[]
     */
    public function getTopicsById($roomId, $ids)
    {
        $this->topicManager->setContextLimit($roomId);
        $this->topicManager->setIDArrayLimit($ids);

        $this->topicManager->select();
        $userList = $this->topicManager->get();

        return $userList->to_array();
    }
    
    public function setFilterConditions(FormInterface $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['hide-deactivated-entries']) {
            if ($formData['hide-deactivated-entries'] === 'only_activated') {
                $this->topicManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
            } else if ($formData['hide-deactivated-entries'] === 'only_deactivated') {
                $this->topicManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_DEACTIVATED);
            } else if ($formData['hide-deactivated-entries'] === 'all') {
                $this->topicManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ACTIVATED_DEACTIVATED);
            }
        }
    }
    
    public function getNewTopic()
    {
        return $this->topicManager->getNewItem();
    }

    public function hideDeactivatedEntries()
    {
        $this->topicManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
    }
}
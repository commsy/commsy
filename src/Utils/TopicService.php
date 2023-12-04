<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\Utils;

use App\Services\LegacyEnvironment;
use cs_manager;
use cs_topic_item;
use cs_topic_manager;
use Symfony\Component\Form\FormInterface;

class TopicService
{
    private readonly cs_topic_manager $topicManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->topicManager = $legacyEnvironment->getEnvironment()->getTopicManager();
        $this->topicManager->reset();
    }

    public function getCountArray($roomId)
    {
        $countTopicArray = [];
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
     */
    public function getTopic($itemId): cs_topic_item
    {
        /** @var cs_topic_item $topic */
        $topic = $this->topicManager->getItem($itemId);

        return $topic;
    }

    /**
     * @param int $roomId
     * @param int $max
     * @param int $start
     *
     * @return cs_topic_item[]
     */
    public function getListTopics($roomId, $max = null, $start = null): array
    {
        $this->topicManager->setContextLimit($roomId);
        if (null !== $max && null !== $start) {
            $this->topicManager->setIntervalLimit($start, $max);
        }

        $this->topicManager->select();
        $topicList = $this->topicManager->get();

        return $topicList->to_array();
    }

    /**
     * @param int   $roomId
     * @param int[] $ids
     *
     * @return cs_topic_item[]
     */
    public function getTopicsById($roomId, $ids): array
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
            if ('only_activated' === $formData['hide-deactivated-entries']) {
                $this->topicManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
            } elseif ('only_deactivated' === $formData['hide-deactivated-entries']) {
                $this->topicManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_DEACTIVATED);
            } elseif ('all' === $formData['hide-deactivated-entries']) {
                $this->topicManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ACTIVATED_DEACTIVATED);
            }
        }
    }

    public function getNewTopic()
    {
        return $this->topicManager->getNewItem();
    }

    public function hideDeactivatedEntries()
    {
        $this->topicManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
    }
}

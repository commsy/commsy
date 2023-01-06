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
use cs_environment;
use cs_group_item;
use cs_group_manager;
use cs_manager;
use Symfony\Component\Form\Form;

class GroupService
{
    private cs_group_manager $groupManager;

    private cs_environment $legacyEnvironment;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->groupManager = $this->legacyEnvironment->getGroupManager();
        $this->groupManager->reset();
    }

    public function getCountArray($roomId)
    {
        $countGroupArray = [];
        $this->groupManager->setContextLimit($roomId);
        $this->groupManager->select();
        $countGroupArray['count'] = sizeof($this->groupManager->get()->to_array());
        $this->groupManager->resetLimits();
        $this->groupManager->select();
        $countGroupArray['countAll'] = $this->groupManager->getCountAll();

        return $countGroupArray;
    }

    public function getGroup($itemId): ?cs_group_item
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->groupManager->getItem($itemId);
    }

    /**
     * @param int    $roomId
     * @param int    $max
     * @param int    $start
     * @param string $sort
     *
     * @return cs_group_item[]
     */
    public function getListGroups($roomId, $max = null, $start = null, $sort = null)
    {
        $this->groupManager->setContextLimit($roomId);
        if (null !== $max && null !== $start) {
            $this->groupManager->setIntervalLimit($start, $max);
        }

        if ($sort) {
            $this->groupManager->setSortOrder($sort);
        }

        $this->groupManager->select();
        $groupList = $this->groupManager->get();

        return $groupList->to_array();
    }

    /**
     * @param int   $roomId
     * @param int[] $ids
     *
     * @return cs_group_item[]
     */
    public function getGroupsById($roomId, $ids)
    {
        $this->groupManager->setContextLimit($roomId);
        $this->groupManager->setIDArrayLimit($ids);

        $this->groupManager->select();
        $userList = $this->groupManager->get();

        return $userList->to_array();
    }

    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['hide-deactivated-entries']) {
            if ('only_activated' === $formData['hide-deactivated-entries']) {
                $this->groupManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
            } elseif ('only_deactivated' === $formData['hide-deactivated-entries']) {
                $this->groupManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_DEACTIVATED);
            } elseif ('all' === $formData['hide-deactivated-entries']) {
                $this->groupManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ACTIVATED_DEACTIVATED);
            }
        }

        // membership
        if ($formData['membership']) {
            $this->groupManager->setUserLimit($this->legacyEnvironment->getCurrentUser()->getItemID());
        }

        // rubrics
        if (isset($formData['rubrics'])) {
            // topic
            if (isset($formData['rubrics']['topic'])) {
                $relatedLabel = $formData['rubrics']['topic'];
                $this->groupManager->setTopicLimit($relatedLabel->getItemId());
            }
        }

        // hashtag
        if (isset($formData['hashtag'])) {
            if (isset($formData['hashtag']['hashtag'])) {
                $hashtag = $formData['hashtag']['hashtag'];
                $itemId = $hashtag->getItemId();
                $this->groupManager->setBuzzwordLimit($itemId);
            }
        }

        // category
        if (isset($formData['category'])) {
            if (isset($formData['category']['category'])) {
                $categories = $formData['category']['category'];

                if (!empty($categories)) {
                    $this->groupManager->setTagArrayLimit($categories);
                }
            }
        }
    }

    public function getNewGroup()
    {
        return $this->groupManager->getNewItem();
    }

    public function hideDeactivatedEntries()
    {
        $this->groupManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
    }
}

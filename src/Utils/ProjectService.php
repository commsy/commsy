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
use cs_manager;
use cs_project_manager;
use Symfony\Component\Form\Form;

class ProjectService
{
    private readonly cs_environment $legacyEnvironment;

    private readonly cs_project_manager $projectManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->projectManager = $this->legacyEnvironment->getProjectManager();
        $this->projectManager->reset();
    }

    public function getListProjects($roomId, $max, $start, $sort)
    {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        $this->projectManager->setContextLimit($roomItem->getContextID());
        $this->projectManager->setCommunityroomLimit($roomId);
        if (null !== $max && null !== $start) {
            $this->projectManager->setIntervalLimit($start, $max);
        }

        if ($sort) {
            $this->projectManager->setSortOrder($sort);
        }

        $this->projectManager->select();
        $projectList = $this->projectManager->get();

        return $projectList->to_array();
    }

    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['activated']) {
            $this->projectManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
        }

        // rubrics
        if ($formData['rubrics']) {
            // group
            if (isset($formData['rubrics']['group'])) {
                $relatedLabel = $formData['rubrics']['group'];
                $this->projectManager->setGroupLimit($relatedLabel->getItemId());
            }

            // topic
            if (isset($formData['rubrics']['topic'])) {
                $relatedLabel = $formData['rubrics']['topic'];
                $this->projectManager->setTopicLimit($relatedLabel->getItemId());
            }
        }

        // hashtag
        if (isset($formData['hashtag'])) {
            if (isset($formData['hashtag']['hashtag'])) {
                $hashtag = $formData['hashtag']['hashtag'];
                $itemId = $hashtag->getItemId();
                $this->projectManager->setBuzzwordLimit($itemId);
            }
        }

        // category
        if (isset($formData['category'])) {
            if (isset($formData['category']['category'])) {
                $categories = $formData['category']['category'];

                if (!empty($categories)) {
                    $this->projectManager->setTagArrayLimit($categories);
                }
            }
        }
    }

    public function getProject($itemId)
    {
        return $this->projectManager->getItem($itemId);
    }

    public function getNewProject()
    {
        return $this->projectManager->getNewItem();
    }

    public function getCountArray($roomId)
    {
        $roomManager = $this->legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        $this->projectManager->reset();
        $this->projectManager->setContextLimit($roomItem->getContextID());
        $this->projectManager->setCommunityroomLimit($roomId);
        $this->projectManager->select();
        $countDatelArray = [];
        $countDatelArray['count'] = sizeof($this->projectManager->get()->to_array());
        $this->projectManager->resetLimits();
        $this->projectManager->setContextLimit($roomItem->getContextID());
        $this->projectManager->setCommunityroomLimit($roomId);
        $this->projectManager->select();
        $countDatelArray['countAll'] = $this->projectManager->getCountAll();

        return $countDatelArray;
    }
}

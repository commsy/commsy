<?php
namespace App\Utils;

use Symfony\Component\Form\Form;

use App\Services\LegacyEnvironment;

class ProjectService
{
    private $legacyEnvironment;

    private $projectManager;

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
        if ($max !== NULL && $start !== NULL) {
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
            $this->projectManager->showNoNotActivatedEntries();
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
        $countDatelArray = array();
        $countDatelArray['count'] = sizeof($this->projectManager->get()->to_array());
        $this->projectManager->resetLimits();
        $this->projectManager->setContextLimit($roomItem->getContextID());
        $this->projectManager->setCommunityroomLimit($roomId);
        $this->projectManager->select();
        $countDatelArray['countAll'] = $this->projectManager->getCountAll();

        return $countDatelArray;
    }
}
<?php
namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class TodoService
{
    private $legacyEnvironment;

    private $todoManager;
    
    private $stepManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->todoManager = $this->legacyEnvironment->getTodoManager();
        $this->todoManager->reset();
        
        $this->stepManager = $this->legacyEnvironment->getStepManager();
        $this->stepManager->reset();
    }

    public function getListTodos($roomId, $max = NULL, $start = NULL, $sort = NULL)
    {
        $this->todoManager->setContextLimit($roomId);
        if ($max !== NULL && $start !== NULL) {
            $this->todoManager->setIntervalLimit($start, $max);
        }

        if ($sort) {
            //$this->todoManager->setOrder($sort);
        }

        $this->todoManager->select();
        $todoList = $this->todoManager->get();

        return $todoList->to_array();
    }

    public function getCountArray($roomId)
    {
        $this->todoManager->setContextLimit($roomId);
        $this->todoManager->select();
        $countTodoArray = array();
        $countTodoArray['count'] = sizeof($this->todoManager->get()->to_array());
        $this->todoManager->resetLimits();
        $this->todoManager->select();
        $countTodoArray['countAll'] = $this->todoManager->getCountAll();

        return $countTodoArray;
    }

    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if (!$formData['activated']) {
            $this->materialManager->showNotActivatedEntries();
        }

        // rubrics
        if ($formData['rubrics']) {
            // group
            if (isset($formData['rubrics']['group'])) {
                $relatedLabel = $formData['rubrics']['group'];
                $this->materialManager->setGroupLimit($relatedLabel->getItemId());
            }
            
            // topic
            if (isset($formData['rubrics']['topic'])) {
                $relatedLabel = $formData['rubrics']['topic'];
                $this->materialManager->setTopicLimit($relatedLabel->getItemId());
            }
            
            // institution
            if (isset($formData['rubrics']['institution'])) {
                $relatedLabel = $formData['rubrics']['institution'];
                $this->materialManager->setInstitutionLimit($relatedLabel->getItemId());
            }
        }

        // hashtag
        if (isset($formData['hashtag'])) {
            if (isset($formData['hashtag']['hashtag'])) {
                $hashtag = $formData['hashtag']['hashtag'];
                $itemId = $hashtag->getItemId();
                $this->materialManager->setBuzzwordLimit($itemId);
            }
        }

        // category
        if (isset($formData['category'])) {
            if (isset($formData['category']['category'])) {
                $categories = $formData['category']['category'];

                if (!empty($categories)) {
                    $this->materialManager->setTagArrayLimit($categories);
                }
            }
        }
    }
    
    public function getTodo($itemId)
    {
        return $this->todoManager->getItem($itemId);
    }
    
    public function getStep($itemId)
    {
        return $this->stepManager->getItem($itemId);
    }
    
    public function getSection($itemId)
    {
        return $this->sectionManager->getItem($itemId);
    }
    
    public function getNewMaterial()
    {
        return $this->materialManager->getNewItem();
    }

    public function getNewSection()
    {
        return $this->sectionManager->getNewItem();
    }
}
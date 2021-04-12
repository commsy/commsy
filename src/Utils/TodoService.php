<?php
namespace App\Utils;

use Symfony\Component\Form\Form;

use App\Services\LegacyEnvironment;
use Symfony\Component\Form\FormInterface;

class TodoService
{
    private $legacyEnvironment;

    /**
     * @var \cs_todos_manager
     */
    private $todoManager;

    /**
     * @var \cs_step_manager
     */
    private $stepManager;

    /**
     * @var \cs_noticed_manager
     */
    private $noticedManager;

    /**
     * @var \cs_reader_manager
     */
    private $readerManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->todoManager = $this->legacyEnvironment->getTodoManager();
        $this->todoManager->reset();
        
        $this->stepManager = $this->legacyEnvironment->getStepManager();
        $this->stepManager->reset();

        $this->noticedManager = $this->legacyEnvironment->getNoticedManager();
        $this->readerManager = $this->legacyEnvironment->getReaderManager();
    }

    /**
     * @param integer $roomId
     * @param integer $max
     * @param integer $start
     * @param string $sort
     * @return \cs_todo_item[]
     */
    public function getListTodos($roomId, $max = NULL, $start = NULL, $sort = NULL)
    {
        $this->todoManager->setContextLimit($roomId);
        if ($max !== NULL && $start !== NULL) {
            $this->todoManager->setIntervalLimit($start, $max);
        }

        if ($sort) {
            $this->todoManager->setSortOrder($sort);
        }

        $this->todoManager->select();
        $todoList = $this->todoManager->get();

        return $todoList->to_array();
    }

    /**
     * @param integer $roomId
     * @param integer[] $idArray
     * @return \cs_todo_item[]
     */
    public function getTodosById($roomId, $idArray) {
        $this->todoManager->setContextLimit($roomId);
        $this->todoManager->setIDArrayLimit($idArray);

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

    public function setFilterConditions(FormInterface $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['hide-deactivated-entries']) {
            if ($formData['hide-deactivated-entries'] === 'only_activated') {
                $this->todoManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
            } else if ($formData['hide-deactivated-entries'] === 'only_deactivated') {
                $this->todoManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_DEACTIVATED);
            } else if ($formData['hide-deactivated-entries'] === 'all') {
                $this->todoManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ACTIVATED_DEACTIVATED);
            }
        }

        // hide completed todos
        if ($formData['hide-completed-entries']) {
            $this->todoManager->setStatusLimit(4);
        }

        // rubrics
        if ($formData['rubrics']) {
            // group
            if (isset($formData['rubrics']['group'])) {
                $relatedLabel = $formData['rubrics']['group'];
                $this->todoManager->setGroupLimit($relatedLabel->getItemId());
            }
            
            // topic
            if (isset($formData['rubrics']['topic'])) {
                $relatedLabel = $formData['rubrics']['topic'];
                $this->todoManager->setTopicLimit($relatedLabel->getItemId());
            }
        }

        // hashtag
        if (isset($formData['hashtag'])) {
            if (isset($formData['hashtag']['hashtag'])) {
                $hashtag = $formData['hashtag']['hashtag'];
                $itemId = $hashtag->getItemId();
                $this->todoManager->setBuzzwordLimit($itemId);
            }
        }

        // category
        if (isset($formData['category'])) {
            if (isset($formData['category']['category'])) {
                $categories = $formData['category']['category'];

                if (!empty($categories)) {
                    $this->todoManager->setTagArrayLimit($categories);
                }
            }
        }
    }

    /**
     * @param integer $itemId
     * @return \cs_todo_item
     */
    public function getTodo($itemId)
    {
        return $this->todoManager->getItem($itemId);
    }
    
    public function getStep($itemId)
    {
        return $this->stepManager->getItem($itemId);
    }

    /**
     * @return \cs_todo_item
     */
    public function getNewTodo()
    {
        return $this->todoManager->getNewItem();
    }

    /**
     * @return \cs_step_item
     */
    public function getNewStep()
    {
        return $this->stepManager->getNewItem();
    }
    
    public function hideDeactivatedEntries()
    {
        $this->todoManager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
    }

    public function hideCompletedEntries()
    {
        $this->todoManager->setStatusLimit(4);
    }

    /** Marks the Todo item with the given ID as read and noticed.
     * @param integer $itemId the identifier of the Todo item to be marked as read and noticed
     * @param bool $markSteps whether the Todo item's Todo steps should be also marked as read and noticed
     * @param bool $markAnnotations whether the Todo item's annotations should be also marked as read and noticed
     */
    public function markTodoReadAndNoticed($itemId, $markSteps = true, $markAnnotations = true)
    {
        if (empty($itemId)) {
            return;
        }

        // todo item
        $item = $this->getTodo($itemId);
        $versionId = $item->getVersionID();
        $this->noticedManager->markNoticed($itemId, $versionId);
        $this->readerManager->markRead($itemId, $versionId);

        // steps
        if ($markSteps === true) {
            $stepList = $item->getStepItemList();
            if (!empty($stepList)) {
                $stepItem = $stepList->getFirst();
                while ($stepItem) {
                    $stepItemID = $stepItem->getItemID();
                    $this->noticedManager->markNoticed($stepItemID, $versionId);
                    $this->readerManager->markRead($stepItemID, $versionId);
                    $stepItem = $stepList->getNext();
                }
            }
        }

        // annotations
        if ($markAnnotations === true) {
            $annotationList = $item->getAnnotationList();
            if (!empty($annotationList)) {
                $annotationItem = $annotationList->getFirst();
                while ($annotationItem) {
                    $annotationItemID = $annotationItem->getItemID();
                    $this->noticedManager->markNoticed($annotationItemID, $versionId);
                    $this->readerManager->markRead($annotationItemID, $versionId);
                    $annotationItem = $annotationList->getNext();
                }
            }
        }
    }
}
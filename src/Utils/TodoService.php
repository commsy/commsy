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
use cs_annotation_item;
use cs_environment;
use cs_manager;
use cs_step_item;
use cs_step_manager;
use cs_todo_item;
use cs_todos_manager;
use Symfony\Component\Form\FormInterface;

class TodoService
{
    private readonly cs_environment $legacyEnvironment;

    private readonly cs_todos_manager $todoManager;

    private readonly cs_step_manager $stepManager;

    public function __construct(
        private readonly ReaderService $readerService,
        LegacyEnvironment $legacyEnvironment
    ) {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->todoManager = $this->legacyEnvironment->getTodosManager();
        $this->todoManager->reset();

        $this->stepManager = $this->legacyEnvironment->getStepManager();
        $this->stepManager->reset();
    }

    /**
     * @param int    $roomId
     * @param int    $max
     * @param int    $start
     * @param string $sort
     *
     * @return cs_todo_item[]
     */
    public function getListTodos($roomId, $max = null, $start = null, $sort = null): array
    {
        $this->todoManager->setContextLimit($roomId);
        if (null !== $max && null !== $start) {
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
     * @param int   $roomId
     * @param int[] $idArray
     *
     * @return cs_todo_item[]
     */
    public function getTodosById($roomId, $idArray): array
    {
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
        $countTodoArray = [];
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
            if ('only_activated' === $formData['hide-deactivated-entries']) {
                $this->todoManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
            } elseif ('only_deactivated' === $formData['hide-deactivated-entries']) {
                $this->todoManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_DEACTIVATED);
            } elseif ('all' === $formData['hide-deactivated-entries']) {
                $this->todoManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ACTIVATED_DEACTIVATED);
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
     * @param int $itemId
     */
    public function getTodo($itemId): cs_todo_item
    {
        return $this->todoManager->getItem($itemId);
    }

    public function getStep($itemId)
    {
        return $this->stepManager->getItem($itemId);
    }

    public function getNewTodo(): cs_todo_item
    {
        return $this->todoManager->getNewItem();
    }

    public function getNewStep(): cs_step_item
    {
        return $this->stepManager->getNewItem();
    }

    public function hideDeactivatedEntries()
    {
        $this->todoManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
    }

    public function hideCompletedEntries()
    {
        $this->todoManager->setStatusLimit(4);
    }

    /** Marks the Todo item with the given ID as read and noticed.
     * @param int $itemId          the identifier of the Todo item to be marked as read and noticed
     * @param bool $markSteps       whether the Todo item's Todo steps should be also marked as read and noticed
     * @param bool $markAnnotations whether the Todo item's annotations should be also marked as read and noticed
     */
    public function markTodoReadAndNoticed(int $itemId, bool $markSteps = true, bool $markAnnotations = true): void
    {
        if (empty($itemId)) {
            return;
        }

        // todo item
        $item = $this->getTodo($itemId);
        $versionId = $item->getVersionID();
        $this->readerService->markRead($itemId, $versionId);

        // steps
        if (true === $markSteps) {
            $steps = $item->getStepItemList();
            foreach ($steps as $step) {
                /** @var cs_step_item $step */
                $this->readerService->markRead($step->getItemId(), $versionId);
            }
        }

        // annotations
        if (true === $markAnnotations) {
            $annotations = $item->getAnnotationList();
            foreach ($annotations as $annotation) {
                /** @var cs_annotation_item $annotation */
                $this->readerService->markRead($annotation->getItemId(), $versionId);
            }
        }
    }
}

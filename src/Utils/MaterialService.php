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
use cs_list;
use cs_manager;
use cs_material_item;
use cs_material_manager;
use cs_noticed_manager;
use cs_reader_manager;
use cs_section_item;
use cs_section_manager;
use Symfony\Component\Form\FormInterface;

class MaterialService
{
    private readonly cs_environment $legacyEnvironment;

    private readonly cs_material_manager $materialManager;

    private readonly cs_section_manager $sectionManager;

    private readonly cs_noticed_manager $noticedManager;

    private readonly cs_reader_manager $readerManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->materialManager = $this->legacyEnvironment->getMaterialManager();
        $this->materialManager->reset();

        $this->sectionManager = $this->legacyEnvironment->getSectionManager();
        $this->sectionManager->reset();

        $this->noticedManager = $this->legacyEnvironment->getNoticedManager();
        $this->readerManager = $this->legacyEnvironment->getReaderManager();
    }

    public function getListMaterials($roomId, $max = null, $start = null, $sort = null)
    {
        $this->materialManager->setContextLimit($roomId);
        if (null !== $max && null !== $start) {
            $this->materialManager->setIntervalLimit($start, $max);
        }

        if ($sort) {
            $this->materialManager->setOrder($sort);
        }

        $this->materialManager->select();
        $materialList = $this->materialManager->get();

        return $materialList->to_array();
    }

    /**
     * @param int   $roomId
     * @param int[] $ids
     *
     * @return cs_material_item[]
     */
    public function getMaterialsById($roomId, $ids)
    {
        $this->materialManager->setContextLimit($roomId);
        $this->materialManager->setIDArrayLimit($ids);

        $this->materialManager->select();
        $todoList = $this->materialManager->get();

        return $todoList->to_array();
    }

    public function getCountArray($roomId)
    {
        $this->materialManager->setContextLimit($roomId);
        $this->materialManager->select();
        $countMaterialArray = [];
        $countMaterialArray['count'] = sizeof($this->materialManager->get()->to_array());
        $this->materialManager->resetLimits();
        $this->materialManager->select();
        $countMaterialArray['countAll'] = $this->materialManager->getCountAll();

        return $countMaterialArray;
    }

    public function setFilterConditions(FormInterface $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['hide-deactivated-entries']) {
            if ('only_activated' === $formData['hide-deactivated-entries']) {
                $this->materialManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
            } elseif ('only_deactivated' === $formData['hide-deactivated-entries']) {
                $this->materialManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_DEACTIVATED);
            } elseif ('all' === $formData['hide-deactivated-entries']) {
                $this->materialManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ACTIVATED_DEACTIVATED);
            }
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

    public function getMaterial($itemId): ?cs_material_item
    {
        return $this->materialManager->getItem($itemId);
    }

    public function getMaterialByVersion($itemId, $versionId): cs_material_item
    {
        return $this->materialManager->getItemByVersion($itemId, $versionId);
    }

    public function getSection($itemId)
    {
        return $this->sectionManager->getItem($itemId);
    }

    public function getNewMaterial()
    {
        return $this->materialManager->getNewItem();
    }

    public function getNewSection(): cs_section_item
    {
        return $this->sectionManager->getNewItem();
    }

    public function getVersionList($itemId): cs_list
    {
        return $this->materialManager->getVersionList($itemId);
    }

    /** Marks the material item with the given ID as read and noticed.
     * @param int  $itemId          the identifier of the material item to be marked as read and noticed
     * @param bool $markSections    whether the material item's sections should be also marked as read and noticed
     * @param bool $markAnnotations whether the materials item's annotations should be also marked as read and noticed
     */
    public function markMaterialReadAndNoticed(int $itemId, bool $markSections = true, bool $markAnnotations = true)
    {
        if (empty($itemId)) {
            return;
        }

        // material item
        $item = $this->getMaterial($itemId);
        $versionId = $item->getVersionID();
        $this->noticedManager->markNoticed($itemId, $versionId);
        $this->readerManager->markRead($itemId, $versionId);

        // sections
        if (true === $markSections) {
            $sectionList = $item->getSectionList();
            if (!empty($sectionList)) {
                $sectionItem = $sectionList->getFirst();
                while ($sectionItem) {
                    $sectionItemID = $sectionItem->getItemID();
                    $this->noticedManager->markNoticed($sectionItemID, $versionId);
                    $this->readerManager->markRead($sectionItemID, $versionId);
                    $sectionItem = $sectionList->getNext();
                }
            }
        }

        // annotations
        if (true === $markAnnotations) {
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

    public function hideDeactivatedEntries()
    {
        $this->materialManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
    }
}

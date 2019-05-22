<?php
namespace App\Utils;

use Symfony\Component\Form\Form;

use App\Services\LegacyEnvironment;
use Symfony\Component\Form\FormInterface;

class MaterialService
{
    private $legacyEnvironment;

    private $materialManager;
    
    private $sectionManager;

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

        $this->materialManager = $this->legacyEnvironment->getMaterialManager();
        $this->materialManager->reset();
        
        $this->sectionManager = $this->legacyEnvironment->getSectionManager();
        $this->sectionManager->reset();

        $this->noticedManager = $this->legacyEnvironment->getNoticedManager();
        $this->readerManager = $this->legacyEnvironment->getReaderManager();
    }

    public function getListMaterials($roomId, $max = NULL, $start = NULL, $sort = NULL)
    {
        $this->materialManager->setContextLimit($roomId);
        if ($max !== NULL && $start !== NULL) {
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
     * @param integer $roomId
     * @param integer[] $ids
     * @return \cs_material_item[]
     */
    public function getMaterialsById($roomId, $ids) {
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
        $countMaterialArray = array();
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
            $this->materialManager->showNoNotActivatedEntries();
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

    public function getMaterial($itemId) : ?\cs_material_item
    {
        return $this->materialManager->getItem($itemId);
    }

    /**
     * @param $itemId
     * @param $versionId
     * @return \cs_material_item
     */
    public function getMaterialByVersion($itemId, $versionId) : \cs_material_item
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

    public function getNewSection() : \cs_section_item
    {
        return $this->sectionManager->getNewItem();
    }
    
    public function getVersionList($itemId) : \cs_list
    {
        return $this->materialManager->getVersionList($itemId);
    }
    
    public function showNoNotActivatedEntries(){
        $this->materialManager->showNoNotActivatedEntries();
    }

    /** Marks the material item with the given ID as read and noticed.
     * @param int $itemId the identifier of the material item to be marked as read and noticed
     * @param bool $markSections whether the material item's sections should be also marked as read and noticed
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
        if ($markSections === true) {
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

    public function hideDeactivatedEntries()
    {
        $this->materialManager->showNoNotActivatedEntries();
    }
}
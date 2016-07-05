<?php
namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class MaterialService
{
    private $legacyEnvironment;

    private $materialManager;
    
    private $sectionManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->materialManager = $this->legacyEnvironment->getMaterialManager();
        $this->materialManager->reset();
        $this->materialManager->showNoNotActivatedEntries();
        
        $this->sectionManager = $this->legacyEnvironment->getSectionManager();
        $this->sectionManager->reset();
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
    
    public function getMaterial($itemId)
    {
        return $this->materialManager->getItem($itemId);
    }
    
    public function getMaterialByVersion($itemId, $versionId)
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

    public function getNewSection()
    {
        return $this->sectionManager->getNewItem();
    }
    
    public function getVersionList($itemId)
    {
        return $this->materialManager->getVersionList($itemId);
    }
}
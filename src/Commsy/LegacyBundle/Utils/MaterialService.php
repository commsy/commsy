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
        
        $this->sectionManager = $this->legacyEnvironment->getSectionManager();
        $this->sectionManager->reset();
    }

    public function getListMaterials($roomId, $max, $start)
    {
        $this->materialManager->reset();
        $this->materialManager->setContextLimit($roomId);
        $this->materialManager->setIntervalLimit($start, $max);

        $this->materialManager->select();
        $materialList = $this->materialManager->get();

        return $materialList->to_array();
    }

    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['activated']) {
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
    }
    
    public function getMaterial($itemId)
    {
        return $this->materialManager->getItem($itemId);
    }
    
    public function getSection($itemId)
    {
        return $this->sectionManager->getItem($itemId);
    }
}
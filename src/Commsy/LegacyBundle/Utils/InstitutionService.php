<?php

namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class InstitutionService
{
    private $legacyEnvironment;

    private $institutionManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;

        $this->institutionManager = $this->legacyEnvironment->getEnvironment()->getInstitutionManager();
        $this->institutionManager->reset();
    }


    public function getCountArray($roomId)
    {
        $this->institutionManager->setContextLimit($roomId);
        $this->institutionManager->select();
        $countInstitution = array();
        $countInstitutionArray['count'] = sizeof($this->institutionManager->get()->to_array());
        $this->institutionManager->resetLimits();
        $this->institutionManager->select();
        $countInstitutionArray['countAll'] = $this->institutionManager->getCountAll();

        return $countInstitutionArray;
    }


    public function getInstitution($itemId)
    {
        $institution = $this->institutionManager->getItem($itemId);
        return $institution;
    }

    public function getListInstitutions($roomId, $max = NULL, $start = NULL, $sort = NULL)
    {
        $this->institutionManager->setContextLimit($roomId);
        if ($max !== NULL && $start !== NULL) {
            $this->institutionManager->setIntervalLimit($start, $max);
        }

        if ($sort) {
            $this->institutionManager->setSortOrder($sort);
        }

        $this->institutionManager->select();
        $institutionList = $this->institutionManager->get();

        return $institutionList->to_array();
    }

    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['hide-deactivated-entries']) {
            $this->institutionManager->showNoNotActivatedEntries();
        }
    }

    public function getNewInstitution()
    {
        $institution = $this->institutionManager->getNewItem();
        $institution->setLabelType(CS_INSTITUTION_TYPE);
        return $institution;
    }

    public function showNoNotActivatedEntries(){
        $this->institutionManager->showNoNotActivatedEntries();
    }
}

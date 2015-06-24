<?php
namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class MaterialManager
{
    private $legacyEnvironment;

    private $materialManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->materialManager = $this->legacyEnvironment->getMaterialManager();
        $this->materialManager->reset();
    }

    public function getListMaterials($roomId, $max, $start)
    {
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
    }
}
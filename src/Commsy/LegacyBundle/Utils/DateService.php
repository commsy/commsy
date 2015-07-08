<?php
namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class DateService
{
    private $legacyEnvironment;

    private $dateManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->dateManager = $this->legacyEnvironment->getDateManager();
        $this->dateManager->reset();
    }

    public function getListDates($roomId, $max, $start)
    {
        $this->dateManager->setContextLimit($roomId);
        $this->dateManager->setIntervalLimit($start, $max);

        $this->dateManager->select();
        $dateList = $this->dateManager->get();

        return $dateList->to_array();
    }

    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['activated']) {
            $this->dateManager->showNoNotActivatedEntries();
        }

        // rubrics
        if ($formData['rubrics']) {
            // group
            if (isset($formData['rubrics']['group'])) {
                $relatedLabel = $formData['rubrics']['group'];
                $this->dateManager->setGroupLimit($relatedLabel->getItemId());
            }
            
            // topic
            if (isset($formData['rubrics']['topic'])) {
                $relatedLabel = $formData['rubrics']['topic'];
                $this->dateManager->setTopicLimit($relatedLabel->getItemId());
            }
            
            // institution
            if (isset($formData['rubrics']['institution'])) {
                $relatedLabel = $formData['rubrics']['institution'];
                $this->dateManager->setInstitutionLimit($relatedLabel->getItemId());
            }
        }
    }
    
    public function getDate($itemId)
    {
        return $this->dateManager->getItem($itemId);
    }
}
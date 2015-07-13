<?php
namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class AnnouncementService
{
    private $legacyEnvironment;

    private $announcementManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();

        $this->announcementManager = $this->legacyEnvironment->getAnnouncementManager();
        $this->announcementManager->reset();
    }

    public function getListAnnouncements($roomId, $max, $start)
    {
        $this->announcementManager->reset();
        $this->announcementManager->setContextLimit($roomId);
        $this->announcementManager->setIntervalLimit($start, $max);

        $this->announcementManager->select();
        $announcementList = $this->announcementManager->get();

        return $announcementList->to_array();
    }

    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['activated']) {
            $this->announcementManager->showNoNotActivatedEntries();
        }

        // rubrics
        if ($formData['rubrics']) {
            // group
            if (isset($formData['rubrics']['group'])) {
                $relatedLabel = $formData['rubrics']['group'];
                $this->announcementManager->setGroupLimit($relatedLabel->getItemId());
            }
            
            // topic
            if (isset($formData['rubrics']['topic'])) {
                $relatedLabel = $formData['rubrics']['topic'];
                $this->announcementManager->setTopicLimit($relatedLabel->getItemId());
            }
            
            // institution
            if (isset($formData['rubrics']['institution'])) {
                $relatedLabel = $formData['rubrics']['institution'];
                $this->announcementManager->setInstitutionLimit($relatedLabel->getItemId());
            }
        }
    }
    
    public function getAnnouncement($itemId)
    {
        return $this->announcementManager->getItem($itemId);
    }
}
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
        $this->announcementManager->showNoNotActivatedEntries();
    }

    public function getCountArray($roomId)
    {
        $this->announcementManager->setContextLimit($roomId);
        $this->announcementManager->select();
        $countAnnouncement = array();
        $countAnnouncementArray['count'] = sizeof($this->announcementManager->get()->to_array());
        $this->announcementManager->resetLimits();
        $this->announcementManager->select();
        $countAnnouncementArray['countAll'] = $this->announcementManager->getCountAll();

        return $countAnnouncementArray;
    }

    public function getListAnnouncements($roomId, $max = NULL, $start = NULL,  $sort = NULL)
    {
        $this->announcementManager->setContextLimit($roomId);
        if ($max !== NULL && $start !== NULL) {
            $this->announcementManager->setIntervalLimit($start, $max);
        }
        if ($sort) {
            $this->announcementManager->setOrder($sort);
        }

        $this->announcementManager->select();
        $announcementList = $this->announcementManager->get();

        return $announcementList->to_array();
    }

    public function setDateLimit(){
        $this->announcementManager->setDateLimit(getCurrentDateTimeInMySQL());
    }

    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if (!$formData['activated']) {
            $this->announcementManager->showNotActivatedEntries();
        }
        
        // active
        if ($formData['active']) {
            $this->announcementManager->setDateLimit(getCurrentDateTimeInMySQL());
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
        // hashtag
        if (isset($formData['hashtag'])) {
            if (isset($formData['hashtag']['hashtag'])) {
                $hashtag = $formData['hashtag']['hashtag'];
                $itemId = $hashtag->getItemId();
                $this->announcementManager->setBuzzwordLimit($itemId);
            }
        }

        // category
        if (isset($formData['category'])) {
            if (isset($formData['category']['category'])) {
                $categories = $formData['category']['category'];

                if (!empty($categories)) {
                    $this->announcementManager->setTagArrayLimit($categories);
                }
            }
        }
    }
    
    public function getAnnouncement($itemId)
    {
        return $this->announcementManager->getItem($itemId);
    }

    public function getNewAnnouncement()
    {
        return $this->announcementManager->getNewItem();
    }
}
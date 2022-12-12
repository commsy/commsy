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
use cs_announcement_item;
use cs_label_item;
use cs_manager;
use Symfony\Component\Form\Form;

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

    public function getCountArray($roomId)
    {
        $countAnnouncementArray = [];
        $this->announcementManager->setContextLimit($roomId);
        $this->announcementManager->select();
        $countAnnouncementArray['count'] = sizeof($this->announcementManager->get()->to_array());
        $this->announcementManager->resetLimits();
        $this->announcementManager->select();
        $countAnnouncementArray['countAll'] = $this->announcementManager->getCountAll();

        return $countAnnouncementArray;
    }

    /**
     * @param int    $roomId
     * @param int    $max
     * @param int    $start
     * @param string $sort
     *
     * @return cs_announcement_item[]
     */
    public function getListAnnouncements($roomId, $max = null, $start = null, $sort = null)
    {
        $this->announcementManager->setContextLimit($roomId);
        if (null !== $max && null !== $start) {
            $this->announcementManager->setIntervalLimit($start, $max);
        }
        if ($sort) {
            $this->announcementManager->setOrder($sort);
        }

        $this->announcementManager->select();
        $announcementList = $this->announcementManager->get();

        return $announcementList->to_array();
    }

    /**
     * @param int   $roomId
     * @param int[] $idArray
     *
     * @return cs_announcement_item[]
     */
    public function getAnnouncementsById($roomId, $idArray)
    {
        $this->announcementManager->setContextLimit($roomId);
        $this->announcementManager->setIDArrayLimit($idArray);

        $this->announcementManager->select();
        $announcementList = $this->announcementManager->get();

        return $announcementList->to_array();
    }

    public function setDateLimit()
    {
        $this->announcementManager->setDateLimit(getCurrentDateTimeInMySQL());
    }

    public function setFilterConditions(Form $filterForm)
    {
        $formData = $filterForm->getData();

        // activated
        if ($formData['hide-deactivated-entries']) {
            if ('only_activated' === $formData['hide-deactivated-entries']) {
                $this->announcementManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
            } elseif ('only_deactivated' === $formData['hide-deactivated-entries']) {
                $this->announcementManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_DEACTIVATED);
            } elseif ('all' === $formData['hide-deactivated-entries']) {
                $this->announcementManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ACTIVATED_DEACTIVATED);
            }
        }

        // active
        if ($formData['hide-invalid-entries']) {
            $this->hideInvalidEntries();
        }

        // rubrics
        if ($formData['rubrics']) {
            // group
            if (isset($formData['rubrics']['group'])) {
                /** @var cs_label_item $relatedLabel */
                $relatedLabel = $formData['rubrics']['group'];
                $this->announcementManager->setGroupLimit($relatedLabel->getItemID());
            }

            // topic
            if (isset($formData['rubrics']['topic'])) {
                /** @var cs_label_item $relatedLabel */
                $relatedLabel = $formData['rubrics']['topic'];
                $this->announcementManager->setTopicLimit($relatedLabel->getItemID());
            }
        }
        // hashtag
        if (isset($formData['hashtag'])) {
            if (isset($formData['hashtag']['hashtag'])) {
                /** @var cs_label_item $hashtag */
                $hashtag = $formData['hashtag']['hashtag'];
                $itemId = $hashtag->getItemID();
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

    public function hideDeactivatedEntries()
    {
        $this->announcementManager->setInactiveEntriesLimit(cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
    }

    public function hideInvalidEntries()
    {
        $this->announcementManager->setDateLimit(getCurrentDateTimeInMySQL());
    }
}

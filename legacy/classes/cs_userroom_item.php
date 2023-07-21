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

use App\Entity\Room;

/**
 * implements a user room item.
 *
 * a user room gets used inside project rooms for bilateral exchange between a single user and the room's moderators
 * NOTE: for user rooms, the context item is the project room that hosts the user room (not the portal item)
 */
class cs_userroom_item extends cs_room_item
{
    /**
     * Room type constant that identifies "user rooms" (which are used inside
     * project rooms for bilateral exchange between a user and the room's moderators).
     *
     * @var string
     */
    public const ROOM_TYPE_USER = 'userroom';

    /**
     * the project room that hosts this user room.
     *
     * @var \cs_project_item
     */
    private $_projectItem = null;

    /**
     * the regular (i.e., non-moderator) user associated with this user room.
     *
     * @var cs_user_item
     */
    private $_userItem = null;

    /**
     * constructor.
     *
     * @param \cs_environment $environment CommSy project environment
     */
    public function __construct($environment)
    {
        cs_context_item::__construct($environment);
        $this->_type = self::ROOM_TYPE_USER;

        $this->_default_rubrics_array[0] = CS_ANNOUNCEMENT_TYPE;
        $this->_default_rubrics_array[1] = CS_TODO_TYPE;
        $this->_default_rubrics_array[3] = CS_MATERIAL_TYPE;
        $this->_default_rubrics_array[4] = CS_DISCUSSION_TYPE;
        $this->_default_rubrics_array[5] = CS_USER_TYPE;

        $this->defaultHomeConf[CS_ANNOUNCEMENT_TYPE] = 'show';
        $this->defaultHomeConf[CS_TODO_TYPE] = 'show';
        $this->defaultHomeConf[CS_MATERIAL_TYPE] = 'show';
        $this->defaultHomeConf[CS_DISCUSSION_TYPE] = 'show';
        $this->defaultHomeConf[CS_USER_TYPE] = 'show';
    }

    public function isUserroom()
    {
        return true;
    }

    public function save($saveOther = true)
    {
        $itemId = $this->getItemID();
        $manager = $this->_environment->getUserRoomManager();
        $this->_save($manager);

        if (empty($itemId)) {
            $this->initTagRootItem();
        }

        $this->updateElastic();
    }

    public function delete()
    {
        parent::delete();

        $manager = $this->_environment->getProjectManager();
        $this->_delete($manager);

        $this->deleteFromElastic();
    }

    // Elastic index

    public function updateElastic()
    {
        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_room');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(Room::class);

        $this->replaceElasticItem($objectPersister, $repository);
    }

    public function deleteFromElastic()
    {
        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_room');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(Room::class);

        $this->deleteElasticItem($objectPersister, $repository);
    }

    // time spread

    /** Returns the time spread for items on home.
     * @return int the time spread
     */
    public function getTimeSpread()
    {
        if ($this->_issetExtra('TIMESPREAD')) {
            return $this->_getExtra('TIMESPREAD');
        }

        return '7';
    }

    /** Sets the time spread for items on home.
     * @param int value the time spread
     */
    public function setTimeSpread($value)
    {
        $this->_addExtra('TIMESPREAD', (int) $value);
    }

    // access rights

    public function isOpenForGuests()
    {
        return false;
    }

    /**
     * is the given user allowed to see this item?
     */
    public function maySee(cs_user_item $userItem)
    {
        if ($userItem->isRoot() || $userItem->isModerator()) {
            return true;
        }

        if ($this->getLinkedUserItemID() == $userItem->getItemID()) {
            return true;
        }

        return false;
    }

    // portal

    public function getPortalID(): ?int
    {
        // NOTE: the context item of a user room is the hosting project room
        return $this->getContextItem()->getContextID();
    }

    public function getPortalItem(): ?cs_portal_item
    {
        $portalId = $this->getPortalID();
        if (empty($portalId)) {
            return null;
        }

        $portalManager = $this->_environment->getPortalManager();
        $portalItem = $portalManager->getItem($portalId);

        return $portalItem;
    }

    // project item

    public function getLinkedProjectItem(): ?cs_project_item
    {
        if (isset($this->_projectItem)) {
            return $this->_projectItem;
        }

        $projectItemId = $this->getLinkedProjectItemID();
        if (isset($projectItemId)) {
            $projectManager = $this->_environment->getProjectManager();
            $projectItem = $projectManager->getItem($projectItemId);
            if (isset($projectItem) and !$projectItem->isDeleted()) {
                $this->_projectItem = $projectItem;
            }

            return $this->_projectItem;
        }

        return null;
    }

    public function getLinkedProjectItemID(): ?int
    {
        if ($this->_issetExtra('PROJECT_ROOM_ITEM_ID')) {
            return $this->_getExtra('PROJECT_ROOM_ITEM_ID');
        }

        return null;
    }

    public function setLinkedProjectItemID($roomId)
    {
        $this->_setExtra('PROJECT_ROOM_ITEM_ID', (int) $roomId);
    }

    // user item

    public function getLinkedUserItem(): ?cs_user_item
    {
        if (isset($this->_userItem)) {
            return $this->_userItem;
        }

        $userItemId = $this->getLinkedUserItemID();
        if (isset($userItemId)) {
            $userManager = $this->_environment->getUserManager();
            if ($userManager->existsItem($userItemId)) {
                $userItem = $userManager->getItem($userItemId);
                if (isset($userItem) and !$userItem->isDeleted()) {
                    $this->_userItem = $userItem;
                }

                return $this->_userItem;
            } else {
                $this->_unsetExtra('USER_ITEM_ID');
                $this->saveWithoutChangingModificationInformation();
                $this->save();
            }
        }

        return null;
    }

    public function getLinkedUserItemID(): ?int
    {
        if ($this->_issetExtra('USER_ITEM_ID')) {
            return $this->_getExtra('USER_ITEM_ID');
        }

        return null;
    }

    public function setLinkedUserItemID($userId)
    {
        $this->_setExtra('USER_ITEM_ID', (int) $userId);
    }

    // usage info

    public function getUsageInfoTextForRubric($rubric)
    {
        return $this->getUsageInfo('USAGE_INFO_TEXT', $rubric);
    }

    public function setUsageInfoTextForRubric($rubric, $string)
    {
        $this->setUsageInfo('USAGE_INFO_TEXT', $rubric, $string);
    }

    public function getUsageInfoTextForRubricForm($rubric)
    {
        return $this->getUsageInfo('USAGE_INFO_FORM_TEXT', $rubric);
    }

    public function setUsageInfoTextForRubricForm($rubric, $string)
    {
        $this->setUsageInfo('USAGE_INFO_FORM_TEXT', $rubric, $string);
    }

    public function getUsageInfoTextForRubricInForm($rubric)
    {
        return $this->getUsageInfo('USAGE_INFO_TEXT', $rubric);
    }

    public function getUsageInfoTextForRubricFormInForm($rubric)
    {
        return $this->getUsageInfo('USAGE_INFO_FORM_TEXT', $rubric);
    }

    private function getUsageInfo(string $key, string $rubric): string
    {
        if ($this->_issetExtra($key)) {
            $usageInfo = $this->_getExtra($key);
            if (empty($usageInfo)) {
                $usageInfo = [];
            } elseif (!is_array($usageInfo)) {
                $usageInfo = XML2Array($usageInfo);
            }
        } else {
            $usageInfo = [];
        }
        $usageInfoKey = mb_strtoupper($rubric, 'UTF-8');
        if (isset($usageInfo[$usageInfoKey]) && !empty($usageInfo[$usageInfoKey])) {
            $usageInfo = $usageInfo[$usageInfoKey];
        } else {
            $usageInfo = '';
        }

        return $usageInfo;
    }

    private function setUsageInfo(string $key, string $rubric, ?string $string)
    {
        if ($this->_issetExtra($key)) {
            $usageInfo = $this->_getExtra($key);
            if (empty($usageInfo)) {
                $usageInfo = [];
            } elseif (!is_array($usageInfo)) {
                $usageInfo = XML2Array($usageInfo);
            }
        } else {
            $usageInfo = [];
        }
        $usageInfoKey = mb_strtoupper($rubric, 'UTF-8');
        if (!empty($string)) {
            $usageInfo[$usageInfoKey] = $string;
        } else {
            if (isset($usageInfo[$usageInfoKey])) {
                unset($usageInfo[$usageInfoKey]);
            }
        }
        $this->_addExtra($key, $usageInfo);
    }
}

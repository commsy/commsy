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

/** upper class of the community item.
 */
include_once 'classes/cs_room_item.php';

/** class for a community
 * this class implements a community item.
 */
class cs_privateroom_item extends cs_room_item
{
    private ?cs_user_item $ownerUserItem = null;

    public $_check_customized_room_id_array = false;
    private $_home_conf_done = false;

    /**
     * Constructor.
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_type = CS_PRIVATEROOM_TYPE;

        // new private room
        $this->_default_rubrics_array[0] = CS_MYROOM_TYPE;
        $this->_default_home_conf_array[CS_MYROOM_TYPE] = 'tiny';
        $this->_default_rubrics_array[2] = CS_DATE_TYPE;
        $this->_default_home_conf_array[CS_DATE_TYPE] = 'tiny';
        $this->_default_rubrics_array[7] = CS_ENTRY_TYPE;
        $this->_default_home_conf_array[CS_ENTRY_TYPE] = 'tiny';
    }

    private function _addPluginInRubricArray()
    {
        if (!$this->_home_conf_done) {
            $i = count($this->_default_rubrics_array) - 1;
            $portal_id = $this->getContextID();
            $plugin_list = $this->_environment->getRubrikPluginClassList($portal_id);
            if (isset($plugin_list)
                and $plugin_list->isNotEmpty()
            ) {
                $plugin = $plugin_list->getFirst();
                while ($plugin) {
                    if ($plugin->inPrivateRoom()
                        and $this->isPluginOn($plugin)
                    ) {
                        ++$i;
                        $this->_plugin_rubrics_array[] = $plugin->getIdentifier();
                        $this->_default_rubrics_array[$i] = $plugin->getIdentifier();
                        $this->_default_home_conf_array[$plugin->getIdentifier()] = $plugin->getHomeStatusDefault();
                    }
                    $plugin = $plugin_list->getNext();
                }
            }
            $this->_home_conf_done = true;
        }
    }

    public function getHomeConf()
    {
        $this->_addPluginInRubricArray();
        $rubrics = parent::getHomeConf();
        $retour = [];
        foreach (explode(',', $rubrics) as $rubric) {
            if (!mb_stristr($rubric, CS_USER_TYPE)) {
                if (!mb_stristr($rubric, CS_MATERIAL_TYPE)
                    and !mb_stristr($rubric, CS_TOPIC_TYPE)
                    and !mb_stristr($rubric, CS_TODO_TYPE)
                ) {
                    $retour[] = $rubric;
                }
            }
        }
        $retour = implode(',', $retour);

        return $retour;
    }

    public function isPrivateRoom()
    {
        return true;
    }

    /** get projects of a project
     * this method returns a list of projects which are linked to the project.
     *
     * @return object cs_list a list of projects (cs_project_item)
     */
    public function getProjectList()
    {
        return $this->getLinkedItemList(CS_MYROOM_TYPE);
    }

    /** get time spread for items on home
     * this method returns the time spread for items on the home of the project project.
     *
     * @return int the time spread
     */
    public function getTimeSpread()
    {
        $retour = '7';
        if ($this->_issetExtra('TIMESPREAD')) {
            $retour = $this->_getExtra('TIMESPREAD');
            if ('1' != $retour and '7' != $retour and '30' != $retour) {
                $retour = '7';
            }
        }

        return $retour;
    }

    /** set time spread for items on home
     * this method sets the time spread for items on the home of the project project.
     *
     * @param int value the time spread
     *
     * @author CommSy Development Group
     */
    public function setTimeSpread($value)
    {
        $this->_addExtra('TIMESPREAD', (int) $value);
    }

    /** get home status for home page
     * this method returns the display status of the home page.
     *
     * @return string the home status
     *
     * @author CommSy Development Group
     */
    public function getHomeStatus()
    {
        $retour = 'normal';
        if ($this->_issetExtra('HOMESTATUS')) {
            $retour = $this->_getExtra('HOMESTATUS');
        }

        return $retour;
    }

    /** set home status for home page
     * this method sets the the display status of the home page.
     *
     * @param string value the home status
     *
     * @author CommSy Development Group
     */
    public function setHomeStatus($value)
    {
        $this->_addExtra('HOMESTATUS', $value);
    }

    /** get template ID for private room
     * this method returns the TemplateID of the private room.
     *
     * @return string the home status
     */
    public function getTemplateID()
    {
        $retour = -1;
        if ($this->_issetExtra('TEMPLATE_ID')) {
            $retour = $this->_getExtra('TEMPLATE_ID');
        }

        return $retour;
    }

    /** set template ID for private room
     * this method sets the template ID of the private room.
     *
     * @param string value the home status
     */
    public function setTemplateID($value)
    {
        $this->_addExtra('TEMPLATE_ID', $value);
    }

    /** get template title for private room
     * this method returns the Templatetitle of the private room.
     *
     * @return string the title of the template
     */
    public function getTemplateTitle()
    {
        $retour = '';
        if ($this->_issetExtra('TEMPLATE_TITLE')) {
            $retour = $this->_getExtra('TEMPLATE_TITLE');
        } else {
            $translator = $this->_environment->getTranslationObject();
            $retour = $translator->getMessage('PRIVATE_ROOM_TITLE');
            unset($translator);
            $owner = $this->getOwnerUserItem();
            $retour .= ' '.$owner->getFullname();
            unset($owner);
        }

        return $retour;
    }

    /** set template title for private room
     * this method sets the template title of the private room.
     *
     * @param string value title of template
     */
    public function setTemplateTitle($value)
    {
        $this->_addExtra('TEMPLATE_TITLE', $value);
    }

    /** set projects of a project item by item id and version id
     * this method sets a list of project item_ids and version_ids which are linked to the project.
     *
     * @param array of project ids, index of id must be 'iid', index of version must be 'vid'
     * Example:
     * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
     *
     * @author CommSy Development Group
     */
    public function setProjectListByID($value)
    {
        $project_array = [];
        foreach ($value as $iid) {
            $tmp_data = [];
            $tmp_data['iid'] = $iid;
            $project_array[] = $tmp_data;
        }
        $this->_setValue(CS_MYROOM_TYPE, $project_array, false);
    }

    /** save private room
     * this method save the private room.
     */
    public function save()
    {
        $item_id = $this->getItemID();
        $manager = $this->_environment->getPrivateRoomManager();
        $current_user = $this->_environment->getCurrentUserItem();

        if (empty($item_id)) {
            $this->setContinuous();
            $this->setServiceLinkActive();
        }

        $this->_save($manager);

        if (empty($item_id)) {
            // create first moderator
            $current_user = $this->getCreatorItem();
            if (!isset($current_user) or empty($current_user)) {
                $current_user = $this->_environment->getCurrentUserItem();
            }
            $new_room_user = $current_user->cloneData();
            $new_room_user->setContextID($this->getItemID());
            $new_room_user->setContextItem($this);
            $new_room_user->makeModerator();
            $new_room_user->makeContactPerson();
            $new_room_user->setVisibleToLoggedIn();
            $new_room_user->save();
            $new_room_user->setCreatorID2ItemID();
            $this->generateLayoutImages();
        }

        // why saving for the second time?
        $this->_save($manager);

        if (empty($item_id)) {
            $this->initTagRootItem();
        }
    }

    /** delete private room
     * this method deletes the private room.
     */
    public function delete()
    {
        $manager = $this->_environment->getPrivateRoomManager();
        $this->_delete($manager);
    }

    public function undelete()
    {
        $manager = $this->_environment->getPrivateRoomManager();
        $this->_undelete($manager);
    }

    public function setRoomContext($value)
    {
    }

    /** is newsletter active ?
     * can be switched at room configuration.
     *
     * true = newletter is active
     * false = newsletter is not active, default
     *
     * @return bool
     */
    public function isPrivateRoomNewsletterActive()
    {
        $retour = false;
        if ($this->_issetExtra('PRIVATEROOMNEWSLETTER')) {
            $active = $this->_getExtra('PRIVATEROOMNEWSLETTER');
            if ('none' != $active) {
                $retour = true;
            }
        }

        return $retour;
    }

    /** set activity of the newsletter, INTERNAL.
     *
     */
    public function setPrivateRoomNewsletterActivity($value)
    {
        $this->_addExtra('PRIVATEROOMNEWSLETTER', $value);
    }

    /** set newsletter active.
     */
    public function getPrivateRoomNewsletterActivity()
    {
        $retour = 'none';
        if ($this->_issetExtra('PRIVATEROOMNEWSLETTER')) {
            $retour = $this->_getExtra('PRIVATEROOMNEWSLETTER');
        }

        return $retour;
    }

    // ##################################################
    // time methods
    // ##################################################

    public function showTime()
    {
        $retour = true;
        $value = $this->_getShowTime();
        if (-1 == $value) {
            $retour = false;
        }

        return $retour;
    }

    public function _getShowTime()
    {
        $retour = '';
        if ($this->_issetExtra('TIME_SHOW')) {
            $retour = $this->_getExtra('TIME_SHOW');
        }

        return $retour;
    }

    public function setShowTime()
    {
        $this->_addExtra('TIME_SHOW', 1);
    }

    public function setNotShowTime()
    {
        $this->_addExtra('TIME_SHOW', -1);
    }

    public function getUsageInfoTextForRubric($rubric)
    {
        $funct = $this->_environment->getCurrentFunction();
        if ($this->_issetExtra('USAGE_INFO_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_TEXT');
            if (empty($retour)) {
                $retour = [];
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = [];
        }
        if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
        } else {
            $retour = '';
        }

        return $retour;
    }

    public function getUsageInfoTextForRubricInForm($rubric)
    {
        $funct = $this->_environment->getCurrentFunction();
        if ($this->_issetExtra('USAGE_INFO_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_TEXT');
            if (empty($retour)) {
                $retour = [];
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = [];
        }
        if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
        } else {
            $retour = '';
        }

        return $retour;
    }

    public function setUsageInfoTextForRubric($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_TEXT')) {
            $value_array = $this->_getExtra('USAGE_INFO_TEXT');
            if (empty($value_array)) {
                $value_array = [];
            } elseif (!is_array($value_array)) {
                $value_array = XML2Array($value_array);
            }
        } else {
            $value_array = [];
        }
        if (!empty($string)) {
            $value_array[mb_strtoupper($rubric, 'UTF-8')] = $string;
        } else {
            if (isset($value_array[mb_strtoupper($rubric, 'UTF-8')])) {
                unset($value_array[mb_strtoupper($rubric, 'UTF-8')]);
            }
        }
        $this->_addExtra('USAGE_INFO_TEXT', $value_array);
    }

    public function setUsageInfoTextForRubricForm($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
            $value_array = $this->_getExtra('USAGE_INFO_FORM_TEXT');
            if (empty($value_array)) {
                $value_array = [];
            } elseif (!is_array($value_array)) {
                $value_array = XML2Array($value_array);
            }
        } else {
            $value_array = [];
        }
        if (!empty($string)) {
            $value_array[mb_strtoupper($rubric, 'UTF-8')] = $string;
        } else {
            if (isset($value_array[mb_strtoupper($rubric, 'UTF-8')])) {
                unset($value_array[mb_strtoupper($rubric, 'UTF-8')]);
            }
        }
        $this->_addExtra('USAGE_INFO_FORM_TEXT', $value_array);
    }

    public function getUsageInfoTextForRubricForm($rubric)
    {
        $funct = $this->_environment->getCurrentFunction();
        if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
            if (empty($retour)) {
                $retour = [];
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = [];
        }
        if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
        } else {
            $retour = '';
        }

        return $retour;
    }

    public function getUsageInfoTextForRubricFormInForm($rubric)
    {
        $funct = $this->_environment->getCurrentFunction();
        if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
            if (empty($retour)) {
                $retour = [];
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = [];
        }
        if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
        } else {
            $retour = '';
        }

        return $retour;
    }

    public function getOwnerUserItem(): ?cs_user_item
    {
        if (!isset($this->ownerUserItem)) {
            $moderator_list = $this->getModeratorList();
            if (1 == $moderator_list->getCount()) {
                /** @var cs_user_item $owner */
                $owner = $moderator_list->getFirst();
                $this->ownerUserItem = $owner;
            }
        }

        return $this->ownerUserItem;
    }

    /** get shown option.
     *
     * @return bool if room is shown on home
     */
    public function isShownInPrivateRoomHome($user_id)
    {
        return false;
    }

    // ##############################################
    // customized room list
    // ##############################################

    public function getCustomizedRoomIDArray()
    {
        $array = [];
        if ($this->_issetExtra('PRIVATEROOMSELECTEDROOMLIST')) {
            $string = $this->_getExtra('PRIVATEROOMSELECTEDROOMLIST');
            $array = explode('$SRID$', $string);
        }
        $array = $this->_cleanCustomizedRoomIDArray($array);

        return $array;
    }

    private function _cleanCustomizedRoomIDArray($array)
    {
        $retour = [];
        if ($this->_check_customized_room_id_array) {
            $retour = $array;
        } else {
            $room_id_array = [];
            foreach ($array as $value) {
                if (!empty($value) and $value > 0) {
                    $room_id_array[] = $value;
                }
            }
            $owner = $this->getOwnerUserItem();
            if (isset($owner)) {
                $user_manager = $this->_environment->getUserManager();
                $room_id_array2 = $user_manager->getMembershipContextIDArrayByUserAndRoomIDLimit($owner->getUserID(), $room_id_array, $owner->getAuthSource());

                foreach ($array as $value) {
                    if ($value < 0 or in_array($value, $room_id_array2)) {
                        $retour[] = $value;
                    }
                }
                $this->_check_customized_room_id_array = true;
                if (array_diff($array, $retour)) {
                    $this->setCustomizedRoomIDArray($retour);
                    $this->save();
                }
            } else {
                $retour = $array;
            }
        }

        return $retour;
    }

    public function setCustomizedRoomIDArray($array)
    {
        if (!empty($array)) {
            $string = implode('$SRID$', $array);
            $this->_addExtra('PRIVATEROOMSELECTEDROOMLIST', $string);
        } else {
            $this->_unsetExtra('PRIVATEROOMSELECTEDROOMLIST');
        }
    }

    public function getCustomizedRoomListCommSy8()
    {
        $retour = null;
        $room_id_array = $this->getCustomizedRoomIDArray();

        if (!empty($room_id_array)
            and !empty($room_id_array[0])
        ) {
            // add grouprooms
            $current_user_item = $this->_environment->getCurrentUserItem();
            $grouproom_list = $current_user_item->getRelatedGroupList();
            if (isset($grouproom_list)
                and $grouproom_list->isNotEmpty()
            ) {
                $grouproom_list->reverse();
                $grouproom_item = $grouproom_list->getFirst();
                while ($grouproom_item) {
                    $project_room_id = $grouproom_item->getLinkedProjectItemID();
                    if (in_array($project_room_id, $room_id_array)) {
                        $room_id_array_temp = [];
                        foreach ($room_id_array as $value) {
                            $room_id_array_temp[] = $value;
                            if ($value == $project_room_id) {
                                if (!in_array($grouproom_item->getItemID(), $room_id_array)) {
                                    $room_id_array_temp[] = $grouproom_item->getItemID();
                                }
                            }
                        }
                        $room_id_array = $room_id_array_temp;
                    }
                    $grouproom_item = $grouproom_list->getNext();
                }
            }

            // store negativ ids and titles and their position
            $negative_ids = [];
            $titles = [];
            $position = 0;
            foreach ($room_id_array as $key => $id) {
                if (mb_stristr($id, '-1$$')) {
                    $titles[$position] = $id;
                    unset($room_id_array[$key]);
                } elseif ($id < 0) {
                    $negative_ids[$position] = $id;
                }

                ++$position;
            }

            $end = $position - 1;

            // get room list
            $room_manager = $this->_environment->getRoomManager();
            $room_manager->setRoomTypeLimit('');
            $room_manager->setIDArrayLimit($room_id_array);
            $room_manager->setOrder('id_array');
            $room_manager->setUserIDLimit($current_user_item->getUserID());
            $room_manager->setAuthSourceLimit($current_user_item->getAuthSource());
            $room_manager->select();
            $retour = $room_manager->get();
            unset($room_manager);
            unset($current_user_item);
        }

        // remove first ---- and clean grouprooms
        if (!empty($retour)) {
            $retour2 = new cs_list();
            $room_item = $retour->getFirst();
            $room_id = 0;

            while ($room_item) {
                if (-1 == $room_item->getItemID()) {
                    $retour2->add($room_item);
                } else {
                    if (!$room_item->isGroupRoom()) {
                        $room_id = $room_item->getItemID();
                        $retour2->add($room_item);
                    } elseif ($room_id == $room_item->getLinkedProjectItemID()) {
                        $retour2->add($room_item);
                    } else {
                        $room_id = $room_item->getItemID();
                        $retour2->add($room_item);
                    }
                }
                $room_item = $retour->getNext();
            }
            $retour = $retour2;
            unset($retour2);
        }
        // remove first ---- and clean grouprooms

        // restore correct negative id and titles
        $item = $retour->getFirst();
        $return = new cs_list();
        for ($position = 0; $position <= $end; ++$position) {
            if (isset($titles[$position])) {
                $room_item = new cs_room_item($this->_environment);
                $room_item->setItemID(-1);
                $room_item->setTitle(mb_substr($titles[$position], 4));
                $return->add($room_item);
            } elseif (isset($negative_ids[$position])) {
                $item->setItemID($negative_ids[$position]);
                $return->add($item);

                $item = $retour->getNext();
            } else {
                $return->add($item);

                $item = $retour->getNext();
            }
        }

        return $return;
    }

    public function getCustomizedRoomList()
    {
        $retour = null;
        $room_id_array = $this->getCustomizedRoomIDArray();
        if (!empty($room_id_array)
            and !empty($room_id_array[0])
        ) {
            // add grouprooms
            $current_user_item = $this->_environment->getCurrentUserItem();
            $grouproom_list = $current_user_item->getRelatedGroupList();
            if (isset($grouproom_list)
                and $grouproom_list->isNotEmpty()
            ) {
                $grouproom_list->reverse();
                $grouproom_item = $grouproom_list->getFirst();
                while ($grouproom_item) {
                    $project_room_id = $grouproom_item->getLinkedProjectItemID();
                    if (in_array($project_room_id, $room_id_array)) {
                        $room_id_array_temp = [];
                        foreach ($room_id_array as $value) {
                            $room_id_array_temp[] = $value;
                            if ($value == $project_room_id) {
                                $room_id_array_temp[] = $grouproom_item->getItemID();
                            }
                        }
                        $room_id_array = $room_id_array_temp;
                    }
                    $grouproom_item = $grouproom_list->getNext();
                }
            }

            // filter
            foreach ($room_id_array as $key => $id) {
                if (mb_stristr($id, '$$')) {
                    unset($room_id_array[$key]);
                }
            }

            // get room list
            $room_manager = $this->_environment->getRoomManager();
            $room_manager->setRoomTypeLimit('');
            $room_manager->setIDArrayLimit($room_id_array);
            $room_manager->setOrder('id_array');
            $room_manager->setUserIDLimit($current_user_item->getUserID());
            $room_manager->setAuthSourceLimit($current_user_item->getAuthSource());
            $room_manager->select();
            $retour = $room_manager->get();
            unset($room_manager);
            unset($current_user_item);
        }

        // remove first ---- and clean grouprooms
        if (!empty($retour)) {
            $retour2 = new cs_list();
            $room_item = $retour->getFirst();
            $room_id = 0;

            $sep = true;
            while ($room_item) {
                if (-1 == $room_item->getItemID()) {
                    if (!$sep) {
                        $retour2->add($room_item);
                    }
                } else {
                    if (!$room_item->isGroupRoom()) {
                        $sep = false;
                        $room_id = $room_item->getItemID();
                        $retour2->add($room_item);
                    } elseif ($room_id == $room_item->getLinkedProjectItemID()) {
                        $sep = false;
                        $retour2->add($room_item);
                    }
                }
                $room_item = $retour->getNext();
            }
            $retour = $retour2;
            unset($retour2);
        }
        // remove first ---- and clean grouprooms
        return $retour;
    }

    public function getTitle()
    {
        $retour = '';
        $title = parent::getTitle();
        if ('PRIVATE_ROOM' == $title
            or 'PRIVATEROOM' == $title
        ) {
            $translator = $this->_environment->getTranslationObject();
            $retour = $translator->getMessage('COMMON_PRIVATEROOM');
        } else {
            $retour = $title;
            if (stristr($retour, '%1')) {
                $user = $this->getOwnerUserItem();
                if (isset($user)) {
                    $retour = str_replace('%1', $user->getFullname(), $retour);
                }
                unset($user);
            } else {
                // use the translated default title for the user's private room (instead of the title of its database entry)
                $translator = $this->_environment->getTranslationObject();
                $retour = $translator->getMessage('COMMON_PRIVATEROOM');
            }
        }

        return $retour;
    }

    public function getTitlePure()
    {
        return parent::getTitle();
    }

    public function setPortletShowNewEntryList()
    {
        $this->_addExtra('PORTLET_SHOW_ENTRY_LIST', '1');
    }

    public function unsetPortletShowNewEntryList()
    {
        $this->_addExtra('PORTLET_SHOW_ENTRY_LIST', '-1');
    }

    public function getPortletNewEntryListCount()
    {
        $retour = 15;
        if ($this->_issetExtra('PORTLET_ENTRY_LIST_COUNT')) {
            $retour = $this->_getExtra('PORTLET_ENTRY_LIST_COUNT');
        }

        return $retour;
    }

    public function setPortletNewEntryListCount($i)
    {
        $this->_addExtra('PORTLET_ENTRY_LIST_COUNT', $i);
    }

    public function getPortletNewEntryListShowUser()
    {
        $retour = '1';
        if ($this->_issetExtra('PORTLET_ENTRY_LIST_SHOW_USER')) {
            $retour = $this->_getExtra('PORTLET_ENTRY_LIST_SHOW_USER');
        }

        return $retour;
    }

    public function setPortletNewEntryListShowUser($i)
    {
        $this->_addExtra('PORTLET_ENTRY_LIST_SHOW_USER', $i);
    }

    public function getCSBarShowWidgets()
    {
        $retour = '-1';
        if ($this->_issetExtra('CS_BAR_SHOW_WIDGETS')) {
            $retour = $this->_getExtra('CS_BAR_SHOW_WIDGETS');
        }

        return $retour;
    }

    public function setCSBarShowWidgets($i)
    {
        $this->_addExtra('CS_BAR_SHOW_WIDGETS', $i);
    }

    public function getCSBarShowCalendar()
    {
        $retour = '-1';
        if ($this->_issetExtra('CS_BAR_SHOW_CALENDAR')) {
            $retour = $this->_getExtra('CS_BAR_SHOW_CALENDAR');
        }

        return $retour;
    }

    public function setCSBarShowCalendar($i)
    {
        $this->_addExtra('CS_BAR_SHOW_CALENDAR', $i);
    }

    public function getCSBarShowOldRoomSwitcher()
    {
        $retour = '-1';
        if ($this->_issetExtra('CS_BAR_SHOW_OLD_ROOM_SWITCHER')) {
            $retour = $this->_getExtra('CS_BAR_SHOW_OLD_ROOM_SWITCHER');
        }

        return $retour;
    }

    public function setCSBarShowOldRoomSwitcher($i)
    {
        $this->_addExtra('CS_BAR_SHOW_OLD_ROOM_SWITCHER', $i);
    }

    public function getCSBarShowStack()
    {
        $retour = '1';
        if ($this->_issetExtra('CS_BAR_SHOW_STACK')) {
            $retour = $this->_getExtra('CS_BAR_SHOW_STACK');
        }

        return $retour;
    }

    public function setCSBarShowStack($i)
    {
        $this->_addExtra('CS_BAR_SHOW_STACK', $i);
    }

    public function isPortfolioEnabled(): bool
    {
        return $this->_issetExtra('CS_BAR_SHOW_PORTFOLIO');
    }

    /**
     * @return $this
     */
    public function setPortfolioEnabled(bool $enabled): self
    {
        if ($enabled) {
            $this->_addExtra('CS_BAR_SHOW_PORTFOLIO', true);
        } else {
            $this->_unsetExtra('CS_BAR_SHOW_PORTFOLIO');
        }

        return $this;
    }

    public function getCSBarShowPortfolio(): string
    {
        return $this->isPortfolioEnabled() ? '1' : '-1';
    }

    public function setCSBarShowPortfolio($i)
    {
        $this->_addExtra('CS_BAR_SHOW_PORTFOLIO', $i);
    }

    public function getCSBarShowConnection()
    {
        $retour = '-1';
        if ($this->_issetExtra('CS_BAR_SHOW_CONNECTION')) {
            $retour = $this->_getExtra('CS_BAR_SHOW_CONNECTION');
        }

        return $retour;
    }

    public function setCSBarShowConnection($i)
    {
        $this->_addExtra('CS_BAR_SHOW_CONNECTION', $i);
    }

    public function showCSBarConnection()
    {
        $retour = false;
        $setCSBarConnection = $this->getCSBarShowConnection();
        if (!empty($setCSBarConnection)
            and '-1' != $setCSBarConnection
        ) {
            $server_item = $this->_environment->getServerItem();
            if ($server_item->isServerConnectionAvailable()) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function switchOnCSBarConnection()
    {
        $this->setCSBarShowConnection('1');
    }

    public function getPortletShowActiveRoomList()
    {
        $retour = true;
        if ($this->_issetExtra('PORTLET_ACTIVE_ROOM_LIST')) {
            if ('-1' == $this->_getExtra('PORTLET_ACTIVE_ROOM_LIST')) {
                $retour = false;
            }
        }

        return $retour;
    }

    public function setPortletShowActiveRoomList()
    {
        $this->_addExtra('PORTLET_ACTIVE_ROOM_LIST', '1');
    }

    public function unsetPortletShowActiveRoomList()
    {
        $this->_addExtra('PORTLET_ACTIVE_ROOM_LIST', '-1');
    }

    public function getPortletActiveRoomCount()
    {
        $retour = 4;
        if ($this->_issetExtra('PORTLET_ACTIVE_ROOM_COUNT')) {
            $retour = $this->_getExtra('PORTLET_ACTIVE_ROOM_COUNT');
        }

        return $retour;
    }

    public function setPortletActiveRoomCount($i)
    {
        $this->_addExtra('PORTLET_ACTIVE_ROOM_COUNT', $i);
    }

    public function setPortletShowSearchBox()
    {
        $this->_addExtra('PORTLET_SHOW_SEARCH_BOX', '1');
    }

    public function unsetPortletShowSearchBox()
    {
        $this->_addExtra('PORTLET_SHOW_SEARCH_BOX', '-1');
    }

    public function getPortletShowSearchBox()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_SEARCH_BOX')) {
            if ('1' == $this->_getExtra('PORTLET_SHOW_SEARCH_BOX')) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setPortletShowRoomWideSearchBox()
    {
        $this->_addExtra('PORTLET_SHOW_ROOMWIDE_SEARCH_BOX', '1');
    }

    public function unsetPortletShowRoomWideSearchBox()
    {
        $this->_addExtra('PORTLET_SHOW_ROOMWIDE_SEARCH_BOX', '-1');
    }

    public function getPortletShowRoomWideSearchBox()
    {
        $retour = true;
        if ($this->_issetExtra('PORTLET_SHOW_ROOMWIDE_SEARCH_BOX')) {
            if ('-1' == $this->_getExtra('PORTLET_SHOW_ROOMWIDE_SEARCH_BOX')) {
                $retour = false;
            }
        }

        return $retour;
    }

    public function setPortletShowWeatherBox()
    {
        $this->_addExtra('PORTLET_SHOW_WEATHER_BOX', '1');
    }

    public function unsetPortletShowWeatherBox()
    {
        $this->_addExtra('PORTLET_SHOW_WEATHER_BOX', '-1');
    }

    public function getPortletShowWeatherBox()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_WEATHER_BOX')) {
            if ('1' == $this->_getExtra('PORTLET_SHOW_WEATHER_BOX')) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function getPortletWeatherLocation()
    {
        $retour = '';
        if ($this->_issetExtra('PORTLET_WEATHER_LOCATION')) {
            $retour = $this->_getExtra('PORTLET_WEATHER_LOCATION');
        }

        return $retour;
    }

    public function setPortletWeatherLocation($account)
    {
        $this->_addExtra('PORTLET_WEATHER_LOCATION', $account);
    }

    public function setPortletShowDokuverserBox()
    {
        $this->_addExtra('PORTLET_SHOW_DOKUVERSER_BOX', '1');
    }

    public function unsetPortletShowDokuverserBox()
    {
        $this->_addExtra('PORTLET_SHOW_DOKUVERSER_BOX', '-1');
    }

    public function getPortletShowDokuverserBox()
    {
        $retour = true;
        if ($this->_issetExtra('PORTLET_SHOW_DOKUVERSER_BOX')) {
            if ('-1' == $this->_getExtra('PORTLET_SHOW_DOKUVERSER_BOX')) {
                $retour = false;
            }
        }

        return $retour;
    }

    public function setPortletShowClockBox()
    {
        $this->_addExtra('PORTLET_SHOW_CLOCK_BOX', '1');
    }

    public function unsetPortletShowClockBox()
    {
        $this->_addExtra('PORTLET_SHOW_CLOCK_BOX', '-1');
    }

    public function getPortletShowClockBox()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_CLOCK_BOX')) {
            if ('1' == $this->_getExtra('PORTLET_SHOW_CLOCK_BOX')) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setPortletShowBuzzwordBox()
    {
        $this->_addExtra('PORTLET_SHOW_BUZZWORD_BOX', '1');
    }

    public function unsetPortletShowBuzzwordBox()
    {
        $this->_addExtra('PORTLET_SHOW_BUZZWORD_BOX', '-1');
    }

    public function getPortletShowBuzzwordBox()
    {
        $retour = true;
        if ($this->_issetExtra('PORTLET_SHOW_BUZZWORD_BOX')) {
            if ('-1' == $this->_getExtra('PORTLET_SHOW_BUZZWORD_BOX')) {
                $retour = false;
            }
        }

        return $retour;
    }

    public function setPortletShowConfigurationBox()
    {
        $this->_addExtra('PORTLET_SHOW_CONFIGURATION_BOX', '1');
    }

    public function unsetPortletShowConfigurationBox()
    {
        $this->_addExtra('PORTLET_SHOW_CONFIGURATION_BOX', '-1');
    }

    public function getPortletShowConfigurationBox()
    {
        $retour = true;
        if ($this->_issetExtra('PORTLET_SHOW_CONFIGURATION_BOX')) {
            if ('-1' == $this->_getExtra('PORTLET_SHOW_CONFIGURATION_BOX')) {
                $retour = false;
            }
        }

        return $retour;
    }

    public function setPortletShowTwitter()
    {
        $this->_addExtra('PORTLET_SHOW_TWITTER', '1');
    }

    public function unsetPortletShowTwitter()
    {
        $this->_addExtra('PORTLET_SHOW_TWITTER', '-1');
    }

    public function getPortletShowTwitter()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_TWITTER')) {
            if ('1' == $this->_getExtra('PORTLET_SHOW_TWITTER')) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function getPortletTwitterAccount()
    {
        $retour = '';
        if ($this->_issetExtra('PORTLET_TWITTER_ACCOUNT')) {
            $retour = $this->_getExtra('PORTLET_TWITTER_ACCOUNT');
        }

        return $retour;
    }

    public function setPortletTwitterAccount($account)
    {
        $this->_addExtra('PORTLET_TWITTER_ACCOUNT', $account);
    }

    public function setPortletShowYouTube()
    {
        $this->_addExtra('PORTLET_SHOW_YOUTUBE', '1');
    }

    public function unsetPortletShowYouTube()
    {
        $this->_addExtra('PORTLET_SHOW_YOUTUBE', '-1');
    }

    public function getPortletShowYouTube()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_YOUTUBE')) {
            if ('1' == $this->_getExtra('PORTLET_SHOW_YOUTUBE')) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function getPortletYouTubeAccount()
    {
        $retour = '';
        if ($this->_issetExtra('PORTLET_YOUTUBE_ACCOUNT')) {
            $retour = $this->_getExtra('PORTLET_YOUTUBE_ACCOUNT');
        }

        return $retour;
    }

    public function setPortletYouTubeAccount($account)
    {
        $this->_addExtra('PORTLET_YOUTUBE_ACCOUNT', $account);
    }

    public function setPortletShowFlickr()
    {
        $this->_addExtra('PORTLET_SHOW_FLICKR', '1');
    }

    public function unsetPortletShowFlickr()
    {
        $this->_addExtra('PORTLET_SHOW_FLICKR', '-1');
    }

    public function getPortletShowFlickr()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_FLICKR')) {
            if ('1' == $this->_getExtra('PORTLET_SHOW_FLICKR')) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function getPortletFlickrID()
    {
        $retour = '';
        if ($this->_issetExtra('PORTLET_FLICKR_ID')) {
            $retour = $this->_getExtra('PORTLET_FLICKR_ID');
        }

        return $retour;
    }

    public function setPortletFlickrID($id)
    {
        $this->_addExtra('PORTLET_FLICKR_ID', $id);
    }

    public function setPortletShowRSS()
    {
        $this->_addExtra('PORTLET_SHOW_RSS', '1');
    }

    public function unsetPortletShowRSS()
    {
        $this->_addExtra('PORTLET_SHOW_RSS', '-1');
    }

    public function getPortletShowRSS()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_RSS')) {
            if ('1' == $this->_getExtra('PORTLET_SHOW_RSS')) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setPortletRSSArray($array)
    {
        $this->_addExtra('PORTLET_RSS_ARRAY', $array);
    }

    public function getPortletRSSArray()
    {
        $retour = [];
        if ($this->_issetExtra('PORTLET_RSS_ARRAY')) {
            $retour = $this->_getExtra('PORTLET_RSS_ARRAY');
        }

        return $retour;
    }

    public function setPortletShowNewItemBox()
    {
        $this->_addExtra('PORTLET_SHOW_NEW_ITEM', '1');
    }

    public function unsetPortletShowNewItemBox()
    {
        $this->_addExtra('PORTLET_SHOW_NEW_ITEM', '-1');
    }

    public function getPortletShowNewItemBox()
    {
        $retour = true;
        if ($this->_issetExtra('PORTLET_SHOW_NEW_ITEM')) {
            if ('-1' == $this->_getExtra('PORTLET_SHOW_NEW_ITEM')) {
                $retour = false;
            }
        }

        return $retour;
    }

    public function setPortletShowNoteBox()
    {
        $this->_addExtra('PORTLET_SHOW_NOTE', '1');
    }

    public function unsetPortletShowNoteBox()
    {
        $this->_addExtra('PORTLET_SHOW_NOTE', '-1');
    }

    public function getPortletShowNoteBox()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_NOTE')) {
            if ('1' == $this->_getExtra('PORTLET_SHOW_NOTE')) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setPortletNoteContent($content)
    {
        $this->_addExtra('PORTLET_NOTE_CONTENT', $content);
    }

    public function getPortletNoteContent()
    {
        $retour = '';
        if ($this->_issetExtra('PORTLET_NOTE_CONTENT')) {
            $retour = $this->_getExtra('PORTLET_NOTE_CONTENT');
        }

        return $retour;
    }

    public function setPortletShowReleasedEntriesBox()
    {
        $this->_addExtra('PORTLET_SHOW_RELEASED_ENTRIES', '1');
    }

    public function unsetPortletShowReleasedEntriesBox()
    {
        $this->_addExtra('PORTLET_SHOW_RELEASED_ENTRIES', '-1');
    }

    public function getPortletShowReleasedEntriesBox()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_RELEASED_ENTRIES')) {
            if ('1' == $this->_getExtra('PORTLET_SHOW_RELEASED_ENTRIES')) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setPortletShowTagBox()
    {
        $this->_addExtra('PORTLET_SHOW_TAG', '1');
    }

    public function unsetPortletShowTagBox()
    {
        $this->_addExtra('PORTLET_SHOW_TAG', '-1');
    }

    public function getPortletShowTagBox()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_TAG')) {
            if ('1' == $this->_getExtra('PORTLET_SHOW_TAG')) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setMyroomDisplayConfig($myroom_array)
    {
        $this->_addExtra('MYROOM_DISPLAY_CONFIG', $myroom_array);
    }

    public function getMyroomDisplayConfig()
    {
        $retour = [];
        if ($this->_issetExtra('MYROOM_DISPLAY_CONFIG')) {
            $retour = $this->_getExtra('MYROOM_DISPLAY_CONFIG');
        }

        return $retour;
    }

    public function issetMyroomDisplayConfig()
    {
        return $this->_issetExtra('MYROOM_DISPLAY_CONFIG');
    }

    public function setMyEntriesDisplayConfig($my_entries_array)
    {
        $this->_addExtra('MY_ENTRIES_DISPLAY_CONFIG', $my_entries_array);
    }

    public function getMyEntriesDisplayConfig()
    {
        $retour = [];
        if ($this->_issetExtra('MY_ENTRIES_DISPLAY_CONFIG')) {
            $retour = $this->_getExtra('MY_ENTRIES_DISPLAY_CONFIG');
        }

        return $retour;
    }

    public function setMyCalendarDisplayConfig($my_calendar_array)
    {
        $this->_addExtra('MY_CALENDAR_DISPLAY_CONFIG', $my_calendar_array);
    }

    public function getMyCalendarDisplayConfig()
    {
        $retour = [];
        if ($this->_issetExtra('MY_CALENDAR_DISPLAY_CONFIG')) {
            $retour = $this->_getExtra('MY_CALENDAR_DISPLAY_CONFIG');
        }

        return $retour;
    }

    /* END OF PORTLET FUNCTIONS
     * *****************
     */

    public function setEmailToCommSy()
    {
        $this->_addExtra('EMAIL_TO_COMMSY', '1');
    }

    public function unsetEmailToCommSy()
    {
        $this->_addExtra('EMAIL_TO_COMMSY', '-1');
    }

    public function getEmailToCommSy()
    {
        $retour = false;
        if ($this->_issetExtra('EMAIL_TO_COMMSY')) {
            if ('1' == $this->_getExtra('EMAIL_TO_COMMSY')) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setEmailToCommSySecret($value)
    {
        $this->_addExtra('EMAIL_TO_COMMSY_SECRET', $value);
    }

    public function getEmailToCommSySecret()
    {
        $retour = false;
        if ($this->_issetExtra('EMAIL_TO_COMMSY_SECRET')) {
            $retour = $this->_getExtra('EMAIL_TO_COMMSY_SECRET');
        }

        return $retour;
    }

    public function setDashboardLayout($data)
    {
        $this->_addExtra('DASHBOARD_LAYOUT', $data);
    }

    public function getDashboardLayout()
    {
        $retour = false;
        if ($this->_issetExtra('DASHBOARD_LAYOUT')) {
            return $this->_getExtra('DASHBOARD_LAYOUT');
        }

        return $retour;
    }

    public function usersCanSetExternalCalendarsUrl()
    {
        return true;
    }

    public function setCalendarSelection($data)
    {
        $this->_addExtra('CALENDAR_SELECTION', $data);
    }

    public function getCalendarSelection()
    {
        $retour = false;
        if ($this->_issetExtra('CALENDAR_SELECTION')) {
            return $this->_getExtra('CALENDAR_SELECTION');
        }

        return $retour;
    }
}

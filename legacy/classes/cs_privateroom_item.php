<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2010 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

/** upper class of the community item
 */
include_once('classes/cs_room_item.php');

/** class for a community
 * this class implements a community item
 */
class cs_privateroom_item extends cs_room_item
{

    var $_user_item = NULL;
    var $_check_customized_room_id_array = false;
    private $_home_conf_done = false;
    private $_send_newsletter = false;

    /**
     * Constructor
     */
    function cs_privateroom_item($environment)
    {
        $this->cs_context_item($environment);
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
                        $i++;
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

    function getHomeConf()
    {
        $this->_addPluginInRubricArray();
        $rubrics = parent::getHomeConf();
        $retour = array();
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

    function isPrivateRoom()
    {
        return true;
    }

    /** get projects of a project
     * this method returns a list of projects which are linked to the project
     *
     * @return object cs_list a list of projects (cs_project_item)
     */
    function getProjectList()
    {
        return $this->getLinkedItemList(CS_MYROOM_TYPE);
    }

    /** get time spread for items on home
     * this method returns the time spread for items on the home of the project project
     *
     * @return integer the time spread
     */
    function getTimeSpread()
    {
        $retour = '7';
        if ($this->_issetExtra('TIMESPREAD')) {
            $retour = $this->_getExtra('TIMESPREAD');
            if ($retour != '1' and $retour != '7' and $retour != '30') {
                $retour = '7';
            }
        }
        return $retour;
    }

    /** set time spread for items on home
     * this method sets the time spread for items on the home of the project project
     *
     * @param integer value the time spread
     *
     * @author CommSy Development Group
     */
    function setTimeSpread($value)
    {
        $this->_addExtra('TIMESPREAD', (int)$value);
    }


    /** get home status for home page
     * this method returns the display status of the home page
     *
     * @return string the home status
     *
     * @author CommSy Development Group
     */
    function getHomeStatus()
    {
        $retour = 'normal';
        if ($this->_issetExtra('HOMESTATUS')) {
            $retour = $this->_getExtra('HOMESTATUS');
        }
        return $retour;
    }

    /** set home status for home page
     * this method sets the the display status of the home page
     *
     * @param string value the home status
     *
     * @author CommSy Development Group
     */
    function setHomeStatus($value)
    {
        $this->_addExtra('HOMESTATUS', $value);
    }

    /** get template ID for private room
     * this method returns the TemplateID of the private room
     *
     * @return string the home status
     */
    function getTemplateID()
    {
        $retour = -1;
        if ($this->_issetExtra('TEMPLATE_ID')) {
            $retour = $this->_getExtra('TEMPLATE_ID');
        }
        return $retour;
    }

    /** set template ID for private room
     * this method sets the template ID of the private room
     *
     * @param string value the home status
     */
    function setTemplateID($value)
    {
        $this->_addExtra('TEMPLATE_ID', $value);
    }

    /** get template title for private room
     * this method returns the Templatetitle of the private room
     *
     * @return string the title of the template
     */
    function getTemplateTitle()
    {
        $retour = '';
        if ($this->_issetExtra('TEMPLATE_TITLE')) {
            $retour = $this->_getExtra('TEMPLATE_TITLE');
        } else {
            $translator = $this->_environment->getTranslationObject();
            $retour = $translator->getMessage('PRIVATE_ROOM_TITLE');
            unset($translator);
            $owner = $this->getOwnerUserItem();
            $retour .= ' ' . $owner->getFullname();
            unset($owner);
        }
        return $retour;
    }

    /** set template title for private room
     * this method sets the template title of the private room
     *
     * @param string value title of template
     */
    function setTemplateTitle($value)
    {
        $this->_addExtra('TEMPLATE_TITLE', $value);
    }

    /** set projects of a project item by item id and version id
     * this method sets a list of project item_ids and version_ids which are linked to the project
     *
     * @param array of project ids, index of id must be 'iid', index of version must be 'vid'
     * Example:
     * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
     *
     * @author CommSy Development Group
     */
    function setProjectListByID($value)
    {
        $project_array = array();
        foreach ($value as $iid) {
            $tmp_data = array();
            $tmp_data['iid'] = $iid;
            $project_array[] = $tmp_data;
        }
        $this->_setValue(CS_MYROOM_TYPE, $project_array, FALSE);
    }

    /** save private room
     * this method save the private room
     */
    function save()
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

            // sync count room redundancy
            $current_portal_item = $this->getContextItem();
            if ($current_portal_item->isCountRoomRedundancy()) {
                $current_portal_item->syncCountPrivateRoomRedundancy(true);
            }
            unset($current_portal_item);
        }

        // why saving for the second time?
        $this->_save($manager);

        if (empty($item_id)) {
            $this->initTagRootItem();
        }
    }

    /** delete private room
     * this method deletes the private room
     */
    function delete()
    {
        $manager = $this->_environment->getPrivateRoomManager();
        $this->_delete($manager);

        // sync count room redundancy
        $current_portal_item = $this->getContextItem();
        if ($current_portal_item->isCountRoomRedundancy()) {
            $current_portal_item->syncCountPrivateRoomRedundancy(true);
        }
        unset($current_portal_item);
    }

    function undelete()
    {
        $manager = $this->_environment->getPrivateRoomManager();
        $this->_undelete($manager);

        // sync count room redundancy
        $current_portal_item = $this->getContextItem();
        if ($current_portal_item->isCountRoomRedundancy()) {
            $current_portal_item->syncCountPrivateRoomRedundancy(true);
        }
        unset($current_portal_item);
    }

    function setRoomContext($value)
    {
    }

    /** is newsletter active ?
     * can be switched at room configuration
     *
     * true = newletter is active
     * false = newsletter is not active, default
     *
     * @return boolean
     */
    function isPrivateRoomNewsletterActive()
    {
        $retour = false;
        if ($this->_issetExtra('PRIVATEROOMNEWSLETTER')) {
            $active = $this->_getExtra('PRIVATEROOMNEWSLETTER');
            if ($active != 'none') {
                $retour = true;
            }
        }
        return $retour;
    }

    /** set activity of the newsletter, INTERNAL
     *
     */
    function setPrivateRoomNewsletterActivity($value)
    {
        $this->_addExtra('PRIVATEROOMNEWSLETTER', $value);
    }

    /** set newsletter active
     */
    function getPrivateRoomNewsletterActivity()
    {
        $retour = 'none';
        if ($this->_issetExtra('PRIVATEROOMNEWSLETTER')) {
            $retour = $this->_getExtra('PRIVATEROOMNEWSLETTER');
        }
        return $retour;
    }

    /** send email newsletter
     * this cron job sends an email newsletter to all users, who wants the newsletter
     * the newsletter describes the activity in the last seven days
     *
     * return array result of cron job
     */
    /* Version 1.7.1*/
    function _sendPrivateRoomNewsletter()
    {
        if (!$this->_send_newsletter) {
            include_once('functions/misc_functions.php');
            $time_start = getmicrotime();

            $retour = array();
            $retour['title'] = 'privateroom newsletter';
            $retour['description'] = 'send activity newsletter to private room user';
            $retour['success'] = false;
            $retour['success_text'] = 'cron failed';

            // get user in room
            $user = $this->getOwnerUserItem();

            if (isset($user)
                and $this->isPrivateRoomNewsletterActive()
                and $this->isPrivateroom()
            ) {
                $mail_array = array();
                $mail_array[] = $user->getRelatedCommSyUserItem()->getRoomEmail();

                // get activity informations for room and send mail
                if (!empty($mail_array)) {
                    // email
                    $id = $user->getItemID();

                    $portal = $this->getContextItem();
                    $room_manager = $this->_environment->getRoomManager();
                    $customizedRoomList = $this->getCustomizedRoomList();
                    if (!isset($customizedRoomList)) {
                        $customizedRoomList = $room_manager->_getRelatedContextListForUser($user->getUserID(), $user->getAuthSource(), $portal->getItemID(), true, true);
                    }

                    $roomList = new cs_list();
                    if (!$customizedRoomList->isEmpty()) {
                        $customizedRoomItem = $customizedRoomList->getFirst();
                        while ($customizedRoomItem) {
                            if ($customizedRoomItem->isPrivateRoom()
                                or !$customizedRoomItem->isShownInPrivateRoomHomeByItemID($id)
                                or !$customizedRoomItem->isOpen()
                                or $customizedRoomItem->getItemID() < 0
                            ) {
                                // do nothing
                            } else {
                                $roomList->add($customizedRoomItem);
                            }

                            $customizedRoomItem = $customizedRoomList->getNext();
                        }
                    }

                    $translator = $this->_environment->getTranslationObject();
                    $translator->setRubricTranslationArray($this->getRubricTranslationArray());
                    $mail_sequence = $this->getPrivateRoomNewsletterActivity();

                    $body = '';
                    $roomItem = $roomList->getFirst();
                    while ($roomItem) {
                        $rubrics = [];

                        $conf = $roomItem->getHomeConf();
                        if (!empty($conf)) {
                            $rubrics = explode(',', $conf);
                        }

                        $numRubrics = count($rubrics);
                        $check_managers = [];
                        foreach ($rubrics as $rubric) {
                            list($rubric_name, $rubric_status) = explode('_', $rubric);

                            if ($rubric_status != 'none') {
                                $check_managers[] = $rubric_name;
                                if ($rubric_name == 'discussion') {
                                    $check_managers[] = 'discarticle';
                                }
                                if ($rubric_name == 'material') {
                                    $check_managers[] = 'section';
                                }
                            }
                        }

                        global $symfonyContainer;
                        $router = $symfonyContainer->get('router');

                        $homeUrl = $router->generate('commsy_room_home', [
                            'roomId' => $roomItem->getItemID(),
                        ], 0);

                        $title = '<a href="' . $homeUrl . '">' . $roomItem->getTitle() . '</a>';
                        $body_title = BR . BR . $title . '' . LF;

                        if ($mail_sequence == 'daily') {
                            $count_total = $roomItem->getPageImpressionsForNewsletter(1);
                            $active = $roomItem->getActiveMembersForNewsletter(1);
                        } else {
                            $count_total = $roomItem->getPageImpressionsForNewsletter(7);
                            $active = $roomItem->getActiveMembersForNewsletter(7);
                        }

                        if ($count_total == 1) {
                            $body_title .= '(' . $count_total . '&nbsp;' . $translator->getMessage('ACTIVITY_PAGE_IMPRESSIONS_SINGULAR') . '; ';
                        } else {
                            $body_title .= '(' . $count_total . '&nbsp;' . $translator->getMessage('ACTIVITY_PAGE_IMPRESSIONS') . '; ';
                        }
                        $body_title .= $translator->getMessage('ACTIVITY_ACTIVE_MEMBERS') . ': ';
                        $body_title .= $active . '):' . BRLF;
                        $body2 = '';

                        /** @var \cs_annotations_manager $annotation_manager */
                        $annotation_manager = $this->_environment->getManager('annotation');
                        $annotation_manager->setContextLimit($roomItem->getItemID());
                        if ($mail_sequence == 'daily') {
                            $annotation_manager->setAgeLimit(1);
                        } else {
                            $annotation_manager->setAgeLimit(7);
                        }
                        $annotation_manager->showNoNotActivatedEntries();
                        $annotation_manager->select();
                        $annotation_list = $annotation_manager->get();
                        $annotationsInNewsletter = [];

                        for ($i = 0; $i < $numRubrics; $i++) {
                            $rubric_array = explode('_', $rubrics[$i]);
                            if ($rubric_array[1] != 'none') {

                                $rubric_manager = $this->_environment->getManager($rubric_array[0]);
                                $rubric_manager->reset();
                                $rubric_manager->setContextLimit($roomItem->getItemID());
                                if ($mail_sequence == 'daily') {
                                    $rubric_manager->setAgeLimit(1);
                                } else {
                                    $rubric_manager->setAgeLimit(7);
                                }

                                if ($rubric_manager instanceof cs_dates_manager) {
                                    $rubric_manager->setDateModeLimit(2);
                                }
                                if ($rubric_manager instanceof cs_user_manager) {
                                    $rubric_manager->setUserLimit();
                                }

                                $rubric_manager->showNoNotActivatedEntries();
                                $rubric_manager->select();
                                $rubric_list = $rubric_manager->get();
                                $rubric_item = $rubric_list->getFirst();

                                $user_manager = $this->_environment->getUserManager();
                                $user_manager->resetLimits();
                                $user_manager->setUserIDLimit($user->getUserID());
                                $user_manager->setAuthSourceLimit($user->getAuthSource());
                                $user_manager->setContextLimit($roomItem->getItemID());
                                $user_manager->select();
                                $user_list = $user_manager->get();

                                $count_entries = 0;
                                if (isset($user_list)
                                    and $user_list->isNotEmpty()
                                    and $user_list->getCount() == 1
                                ) {
                                    $ref_user = $user_list->getFirst();
                                    if (isset($ref_user)
                                        and $ref_user->getItemID() > 0
                                    ) {
                                        $temp_body = '';
                                        while ($rubric_item) {
                                            $noticed_manager = $this->_environment->getNoticedManager();
                                            $noticed = $noticed_manager->getLatestNoticedForUserByID($rubric_item->getItemID(), $ref_user->getItemID());
                                            if (empty($noticed)) {
                                                $info_text = ' <span class="changed">[' . $translator->getMessage('COMMON_NEW') . ']</span>';
                                            } elseif ($noticed['read_date'] < $rubric_item->getModificationDate()) {
                                                $info_text = ' <span class="changed">[' . $translator->getMessage('COMMON_CHANGED') . ']</span>';
                                            } else {
                                                $info_text = '';
                                            }
                                            $annotation_item = $annotation_list->getFirst();
                                            $annotation_count = 0;
                                            while ($annotation_item) {
                                                $annotation_noticed = $noticed_manager->getLatestNoticedForUserByID($annotation_item->getItemID(), $ref_user->getItemID());
                                                if (empty($annotation_noticed)) {
                                                    $linked_item = $annotation_item->getLinkedItem();
                                                    if ($linked_item->getItemID() == $rubric_item->getItemID()) {
                                                        $annotation_count++;
                                                        $annotationsInNewsletter[] = $annotation_item;
                                                    }
                                                }
                                                $annotation_item = $annotation_list->getNext();
                                            }
                                            if ($annotation_count == 1) {
                                                $info_text .= ' <span class="changed">[' . $translator->getMessage('COMMON_NEW_ANNOTATION') . ']</span>';
                                            } else if ($annotation_count > 1) {
                                                $info_text .= ' <span class="changed">[' . $translator->getMessage('COMMON_NEW_ANNOTATIONS') . ']</span>';
                                            }

                                            if (!empty($info_text)) {
                                                $count_entries++;
                                                $params = array();
                                                $params['iid'] = $rubric_item->getItemID();
                                                $title = '';
                                                if ($rubric_item->isA(CS_USER_TYPE)) {
                                                    $title .= $this->_environment->getTextConverter()->text_as_html_short($rubric_item->getFullname());
                                                } else {
                                                    $title .= $this->_environment->getTextConverter()->text_as_html_short($rubric_item->getTitle());
                                                }
                                                if ($rubric_item->isA(CS_LABEL_TYPE)) {
                                                    $mod = $rubric_item->getLabelType();
                                                } else {
                                                    $mod = $rubric_item->getType();
                                                }

                                                $title .= $info_text;

                                                $urlParameters = [
                                                    'roomId' => $roomItem->getItemID(),
                                                    'itemId' => $params['iid'],
                                                ];

                                                if ($mod == 'material') {
                                                    $urlParameters['versionId'] = 0;
                                                }

                                                $detailUrl = $router->generate('commsy_' . $mod . '_detail', $urlParameters, 0);

                                                $ahref_curl = '<a href="' . $detailUrl . '">' . $title . '</a>';

                                                $temp_body .= BR . '&nbsp;&nbsp;- ' . $ahref_curl;
                                            }

                                            $rubric_item = $rubric_list->getNext();
                                        }
                                    }
                                }

                                switch (mb_strtoupper($rubric_array[0], 'UTF-8')) {
                                    case 'ANNOUNCEMENT':
                                        $tempMessage = $translator->getMessage('ANNOUNCEMENT_INDEX');
                                        break;
                                    case 'DATE':
                                        $tempMessage = $translator->getMessage('DATES_INDEX');
                                        break;
                                    case 'DISCUSSION':
                                        $tempMessage = $translator->getMessage('DISCUSSION_INDEX');
                                        break;
                                    case 'GROUP':
                                        $tempMessage = $translator->getMessage('GROUP_INDEX');
                                        break;
                                    case 'INSTITUTION':
                                        $tempMessage = $translator->getMessage('INSTITUTION_INDEX');
                                        break;
                                    case 'MATERIAL':
                                        $tempMessage = $translator->getMessage('MATERIAL_INDEX');
                                        break;
                                    case 'MYROOM':
                                        $tempMessage = $translator->getMessage('MYROOM_INDEX');
                                        break;
                                    case 'PROJECT':
                                        $tempMessage = $translator->getMessage('PROJECT_INDEX');
                                        break;
                                    case 'TODO':
                                        $tempMessage = $translator->getMessage('TODO_INDEX');
                                        break;
                                    case 'TOPIC':
                                        $tempMessage = $translator->getMessage('TOPIC_INDEX');
                                        break;
                                    case 'USER':
                                        $tempMessage = $translator->getMessage('USER_INDEX');
                                        break;
                                    case 'ENTRY':
                                        $tempMessage = $translator->getMessage('ENTRY_INDEX');
                                        break;
                                    default:
                                        $tempMessage = $translator->getMessage('COMMON_MESSAGETAG_ERROR' . ' cs_privateroom_item(456) ');
                                        break;
                                }

                                if ($count_entries == 1) {
                                    $listUrl = $router->generate('commsy_' . $rubric_array[0] . '_list', [
                                        'roomId' => $roomItem->getItemID(),
                                    ], 0);

                                    $ahref_curl = '<a href="' . $listUrl . '">' . $tempMessage . '</a>';
                                    $body2 .= '&nbsp;&nbsp;' . $ahref_curl;
                                    $body2 .= ' <span style="font-size:8pt;">(' . $count_entries . ' ' . $translator->getMessage('NEWSLETTER_NEW_SINGLE_ENTRY') . ')</span>';
                                } elseif ($count_entries > 1) {
                                    $listUrl = $router->generate('commsy_' . $rubric_array[0] . '_list', [
                                        'roomId' => $roomItem->getItemID(),
                                    ], 0);

                                    $ahref_curl = '<a href="' . $listUrl . '">' . $tempMessage . '</a>';
                                    $body2 .= '&nbsp;&nbsp;' . $ahref_curl;
                                    $body2 .= ' <span style="font-size:8pt;">(' . $count_entries . ' ' . $translator->getMessage('NEWSLETTER_NEW_ENTRIES') . ')</span>';
                                }
                                if (!empty($body2) and !empty($temp_body)) {
                                    $body2 .= $temp_body . BRLF . LF;
                                }
                            }
                        }

                        $annotation_item = $annotation_list->getFirst();
                        $annotationsStillToSend = array();
                        while ($annotation_item) {
                            if (!in_array($annotation_item, $annotationsInNewsletter)) {
                                $annotationsStillToSend[] = $annotation_item;
                            }
                            $annotation_item = $annotation_list->getNext();
                        }

                        $annotation_info_text = '';
                        if (count($annotationsStillToSend) == 1) {
                            $annotation_info_text .= '&nbsp;&nbsp;<span class="changed">' . $translator->getMessage('COMMON_NEW_ANNOTATION_ADDITIONAL') . ':</span>';
                        } else if (count($annotationsStillToSend) > 1) {
                            $annotation_info_text .= '&nbsp;&nbsp;<span class="changed">' . $translator->getMessage('COMMON_NEW_ANNOTATIONS_ADDITIONAL') . ':</span>';
                        }

                        if (!empty($annotation_info_text)) {
                            $temp_body_annotation = BRLF . LF . $annotation_info_text;
                            foreach ($annotationsStillToSend as $annotationStillToSend) {
                                $annotatedItem = $annotationStillToSend->getLinkedItem();
                                $annotationTitle = '';
                                if ($annotationStillToSend->getTitle() != '') {
                                    $annotationTitle = ' (' . $annotationStillToSend->getTitle() . ')';
                                }

                                $annotatedItemUrl = $router->generate('commsy_' . $annotatedItem->getItemType() . '_detail', [
                                    'roomId' => $roomItem->getItemID(),
                                    'itemId' => $annotatedItem->getItemId(),
                                ], 0);


                                $ahref_curl = '<a href="' . $annotatedItemUrl . '">' . $annotatedItem->getTitle() . '</a>' . $annotationTitle;
                                $temp_body_annotation .= BR . '&nbsp;&nbsp;&nbsp;&nbsp;- ' . $ahref_curl;
                            }
                            $body2 .= $temp_body_annotation . BRLF . LF;
                        }

                        if (!empty($body2)) {
                            $body .= $body_title;
                            $body2 .= BRLF;
                            $body .= $body2;
                        } else {
                            $body .= $body_title;
                            $body2 .= '&nbsp;&nbsp;' . $translator->getMessage('COMMON_NO_NEW_ENTRIES') . BRLF;
                            $body .= $body2;
                        }

                        $roomItem = $roomList->getNext();
                    }
                }

                if (empty($body)) {
                    $translator->getMessage('COMMON_NO_NEW_ENTRIES') . LF;
                }
                $body .= LF;
                $portal = $this->getContextItem();
                $portal_title = '';
                if (isset($portal)) {
                    $portal_title = $portal->getTitle();
                }
                if ($mail_sequence == 'daily') {
                    $body = $translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_HEADER_DAILY', $portal_title) . LF . LF . $body;
                } else {
                    $body = $translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_HEADER_WEEKLY', $portal_title) . LF . LF . $body;
                }

                $body .= BRLF . BR . '-----------------------------' . BRLF . LF . $translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_FOOTER');

                $from = $translator->getMessage('SYSTEM_MAIL_MESSAGE', $portal_title);
                $to = implode($mail_array, ',');
                if ($mail_sequence == 'daily') {
                    $subject = $translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_DAILY') . ': ' . $portal_title;
                } else {
                    $subject = $translator->getMessage('PRIVATEROOM_MAIL_SUBJECT_WEEKLY') . ': ' . $portal_title;
                }

                // send email
                global $symfonyContainer;
                $emailFrom = $symfonyContainer->getParameter('commsy.email.from');

                $emailHasCorrectFormat = true;
                foreach ($mail_array as $temp_mail) {
                    if (!filter_var($temp_mail, FILTER_VALIDATE_EMAIL)) {
                        $emailHasCorrectFormat = false;
                    }
                }

                if ($emailHasCorrectFormat) {
                    $message = \Swift_Message::newInstance()
                        ->setSubject($subject)
                        ->setBody($body, 'text/html')
                        ->setFrom([$emailFrom => $from])
                        ->setTo($mail_array);

                    if ($symfonyContainer->get('mailer')->send($message, $failures)) {
                        $retour['success'] = true;
                        $retour['success_text'] = 'send newsletter to ' . $to;
                        $this->_send_newsletter = true;
                    }

                    // flush queue manually
                    $mailer = $symfonyContainer->get('mailer');
                    $transport = $symfonyContainer->get('swiftmailer.transport.real');

                    $spool = $mailer->getTransport()->getSpool();
                    $spool->flushQueue($transport);
                }
            } else {
                $retour['success'] = true;
                $retour['success_text'] = 'no user in room want the newsletter';
            }

            $time_end = getmicrotime();
            $time = round($time_end - $time_start, 0);
            $retour['time'] = $time;

            return $retour;
        }
    }

    ###################################################
    # time methods
    ###################################################


    function showTime()
    {
        $retour = true;
        $value = $this->_getShowTime();
        if ($value == -1) {
            $retour = false;
        }
        return $retour;
    }

    function _getShowTime()
    {
        $retour = '';
        if ($this->_issetExtra('TIME_SHOW')) {
            $retour = $this->_getExtra('TIME_SHOW');
        }
        return $retour;
    }

    function setShowTime()
    {
        $this->_addExtra('TIME_SHOW', 1);
    }

    function setNotShowTime()
    {
        $this->_addExtra('TIME_SHOW', -1);
    }

    function getUsageInfoTextForRubric($rubric)
    {
        $funct = $this->_environment->getCurrentFunction();
        if ($this->_issetExtra('USAGE_INFO_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_TEXT');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }
        if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
        } else {
            $retour = '';
        }
        return $retour;
    }

    function getUsageInfoTextForRubricInForm($rubric)
    {
        $funct = $this->_environment->getCurrentFunction();
        if ($this->_issetExtra('USAGE_INFO_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_TEXT');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }
        if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
        } else {
            $retour = '';
        }
        return $retour;
    }

    function setUsageInfoTextForRubric($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_TEXT')) {
            $value_array = $this->_getExtra('USAGE_INFO_TEXT');
            if (empty($value_array)) {
                $value_array = array();
            } elseif (!is_array($value_array)) {
                $value_array = XML2Array($value_array);
            }
        } else {
            $value_array = array();
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

    function setUsageInfoTextForRubricForm($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
            $value_array = $this->_getExtra('USAGE_INFO_FORM_TEXT');
            if (empty($value_array)) {
                $value_array = array();
            } elseif (!is_array($value_array)) {
                $value_array = XML2Array($value_array);
            }
        } else {
            $value_array = array();
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


    function getUsageInfoTextForRubricForm($rubric)
    {
        $funct = $this->_environment->getCurrentFunction();
        if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }
        if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
        } else {
            $retour = '';
        }
        return $retour;
    }

    function getUsageInfoTextForRubricFormInForm($rubric)
    {
        $funct = $this->_environment->getCurrentFunction();
        if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
            if (empty($retour)) {
                $retour = array();
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = array();
        }
        if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
        } else {
            $retour = '';
        }
        return $retour;
    }

    function getOwnerUserItem()
    {
        if (!isset($this->_user_item)) {
            $moderator_list = $this->getModeratorList();
            if ($moderator_list->getCount() == 1) {
                $this->_user_item = $moderator_list->getFirst();
            }
        }
        return $this->_user_item;
    }


    function _cronWeekly()
    {
        // you can link weekly cron jobs here like this
        // $cron_array[] = $this->_sendEmailNewsLetter();
        $cron_array = array();

        ################ BEGIN ###################
        # email newsletter
        ##########################################
        if ($this->isPrivateRoomNewsletterActive() and $this->isOpen()) {
            $period = $this->getPrivateRoomNewsletterActivity();
            if ($period == 'weekly') {
                $cron_array[] = $this->_sendPrivateRoomNewsletter();
            }
            unset($period);
        }
        ##########################################
        # email newsletter
        ################# END ####################

        return $cron_array;
    }

    function _cronDaily()
    {
        // you can link daily cron jobs here like this
        // $cron_array[] = $this->_sendEmailNewsLetter();
        $cron_array = array();

        $father_cron_array = parent::_cronDaily();
        $cron_array = array_merge($father_cron_array, $cron_array);

        ################ BEGIN ###################
        # email newsletter
        ##########################################

        if ($this->isPrivateRoomNewsletterActive() and $this->isOpen()) {
            $period = $this->getPrivateRoomNewsletterActivity();
            if ($period == 'daily') {
                $cron_array[] = $this->_sendPrivateRoomNewsletter();
            }
            unset($period);
        }

        ##########################################
        # email newsletter
        ################# END ####################

        return $cron_array;
    }

    /** get shown option
     *
     * @return boolean if room is shown on home
     */
    function isShownInPrivateRoomHome($user_id)
    {
        return false;
    }

    ###############################################
    # customized room list
    ###############################################

    public function getCustomizedRoomIDArray()
    {
        $array = array();
        if ($this->_issetExtra('PRIVATEROOMSELECTEDROOMLIST')) {
            $string = $this->_getExtra('PRIVATEROOMSELECTEDROOMLIST');
            $array = explode('$SRID$', $string);
        }
        $array = $this->_cleanCustomizedRoomIDArray($array);
        return $array;
    }

    private function _cleanCustomizedRoomIDArray($array)
    {
        $retour = array();
        if ($this->_check_customized_room_id_array) {
            $retour = $array;
        } else {
            $room_id_array = array();
            foreach ($array as $value) {
                if (!empty($value) and $value > 0) {
                    $room_id_array[] = $value;
                }
            }
            $owner = $this->getOwnerUserItem();
            if (isset($owner)) {
                $user_manager = $this->_environment->getUserManager();
                $room_id_array2 = $user_manager->getMembershipContextIDArrayByUserAndRoomIDLimit($owner->getUserID(), $room_id_array, $owner->getAuthSource());

                // archive
                if (!$this->_environment->isArchiveMode()) {
                    $this->_environment->activateArchiveMode();
                    $user_manager2 = $this->_environment->getUserManager();
                    $room_id_array3 = $user_manager2->getMembershipContextIDArrayByUserAndRoomIDLimit($owner->getUserID(), $room_id_array, $owner->getAuthSource());
                    if (!empty($room_id_array3)) {
                        $room_id_array2 = array_merge($room_id_array2, $room_id_array3);
                    }
                    unset($user_manager2);
                    $this->_environment->deactivateArchiveMode();
                }
                // archive end

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
        $retour = NULL;
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
                        $room_id_array_temp = array();
                        foreach ($room_id_array as $value) {
                            $room_id_array_temp[] = $value;
                            if ($value == $project_room_id) {
                                if (!in_array($grouproom_item->getItemID(), $room_id_array))
                                    $room_id_array_temp[] = $grouproom_item->getItemID();
                            }
                        }
                        $room_id_array = $room_id_array_temp;
                    }
                    $grouproom_item = $grouproom_list->getNext();
                }
            }

            // store negativ ids and titles and their position
            $negative_ids = array();
            $titles = array();
            $position = 0;
            foreach ($room_id_array as $key => $id) {
                if (mb_stristr($id, '-1$$')) {
                    $titles[$position] = $id;
                    unset($room_id_array[$key]);
                } elseif ($id < 0) {
                    $negative_ids[$position] = $id;
                }

                $position++;
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
                if ($room_item->getItemID() == -1) {
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
        $return = new cs_list;
        for ($position = 0; $position <= $end; $position++) {
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
        $retour = NULL;
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
                        $room_id_array_temp = array();
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
                if (mb_stristr($id, '$$')) unset($room_id_array[$key]);
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
                if ($room_item->getItemID() == -1) {
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
        if ($title == 'PRIVATE_ROOM'
            or $title == 'PRIVATEROOM'
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
            }
        }
        return $retour;
    }

    public function getTitlePure()
    {
        return parent::getTitle();
    }

    public function withActivatingContent()
    {
        //$result = false;
        //$manager = $this->_environment->getMyRoomManager();
        //$user = $this->_environment->getCurrentUserItem();
        //$project_list = $manager->getRelatedContextListForUser($user->getUserID(),$user->getAuthSource(),$this->_environment->getCurrentPortalID());
        //$project = $project_list->getFirst();
        //while($project){
        //   if($project->withActivatingContent()){
        //      $result = true;
        //   }
        //   $project = $project_list->getNext();
        //}
        //return $result;
        return true;
    }

    ##################################
    # save rubric selections - BEGIN
    ##################################

    private function _getRubrikSelectioArray()
    {
        $retour = array();
        if ($this->_issetExtra('SELECTION')) {
            $retour = $this->_getExtra('SELECTION');
        }
        return $retour;
    }

    private function _setRubrikSelection($array)
    {
        if (isset($array)) {
            $this->_setExtra('SELECTION', $array);
        }
    }

    public function getRubrikSelection($rubric, $selection)
    {
        $retour = '';
        if ($this->_issetExtra('SELECTION')) {
            $sel_array = $this->_getExtra('SELECTION');
            if (!empty($sel_array[$rubric][$selection])) {
                $retour = $sel_array[$rubric][$selection];
            }
        }
        return $retour;
    }

    public function setRubrikSelection($rubric, $selection, $value)
    {
        if ($this->_issetExtra('SELECTION')) {
            $sel_array = $this->_getExtra('SELECTION');
        } else {
            $sel_array = array();
        }
        $sel_array[$rubric][$selection] = $value;
        $this->_setRubrikSelection($sel_array);
    }

    ##################################
    # save rubric selections - END
    ##################################

    /* PORTLET FUNCTIONS
     * *****************
     */
    function getPortletColumnCount()
    {
        $retour = 3;
        if ($this->_issetExtra('PORTLET_COLUMN_COUNT')) {
            $retour = $this->_getExtra('PORTLET_COLUMN_COUNT');
        }
        return $retour;
    }

    function setPortletColumnCount($count)
    {
        $this->_addExtra('PORTLET_COLUMN_COUNT', $count);
    }


    function getPortletShowNewEntryList()
    {
        $retour = true;
        if ($this->_issetExtra('PORTLET_SHOW_ENTRY_LIST')) {
            if ($this->_getExtra('PORTLET_SHOW_ENTRY_LIST') == '-1') {
                $retour = false;
            }
        }
        return $retour;
    }

    function setPortletShowNewEntryList()
    {
        $this->_addExtra('PORTLET_SHOW_ENTRY_LIST', '1');
    }

    function unsetPortletShowNewEntryList()
    {
        $this->_addExtra('PORTLET_SHOW_ENTRY_LIST', '-1');
    }

    function getPortletNewEntryListCount()
    {
        $retour = 15;
        if ($this->_issetExtra('PORTLET_ENTRY_LIST_COUNT')) {
            $retour = $this->_getExtra('PORTLET_ENTRY_LIST_COUNT');
        }
        return $retour;
    }

    function setPortletNewEntryListCount($i)
    {
        $this->_addExtra('PORTLET_ENTRY_LIST_COUNT', $i);
    }

    function getPortletNewEntryListShowUser()
    {
        $retour = '1';
        if ($this->_issetExtra('PORTLET_ENTRY_LIST_SHOW_USER')) {
            $retour = $this->_getExtra('PORTLET_ENTRY_LIST_SHOW_USER');
        }
        return $retour;
    }

    function setPortletNewEntryListShowUser($i)
    {
        $this->_addExtra('PORTLET_ENTRY_LIST_SHOW_USER', $i);
    }


    function getCSBarShowWidgets()
    {
        $retour = '-1';
        if ($this->_issetExtra('CS_BAR_SHOW_WIDGETS')) {
            $retour = $this->_getExtra('CS_BAR_SHOW_WIDGETS');
        }
        return $retour;
    }

    function setCSBarShowWidgets($i)
    {
        $this->_addExtra('CS_BAR_SHOW_WIDGETS', $i);
    }

    function getCSBarShowCalendar()
    {
        $retour = '-1';
        if ($this->_issetExtra('CS_BAR_SHOW_CALENDAR')) {
            $retour = $this->_getExtra('CS_BAR_SHOW_CALENDAR');
        }
        return $retour;
    }

    function setCSBarShowCalendar($i)
    {
        $this->_addExtra('CS_BAR_SHOW_CALENDAR', $i);
    }

    function getCSBarShowOldRoomSwitcher()
    {
        $retour = '-1';
        if ($this->_issetExtra('CS_BAR_SHOW_OLD_ROOM_SWITCHER')) {
            $retour = $this->_getExtra('CS_BAR_SHOW_OLD_ROOM_SWITCHER');
        }
        return $retour;
    }

    function setCSBarShowOldRoomSwitcher($i)
    {
        $this->_addExtra('CS_BAR_SHOW_OLD_ROOM_SWITCHER', $i);
    }

    function getCSBarShowStack()
    {
        $retour = '1';
        if ($this->_issetExtra('CS_BAR_SHOW_STACK')) {
            $retour = $this->_getExtra('CS_BAR_SHOW_STACK');
        }
        return $retour;
    }

    function setCSBarShowStack($i)
    {
        $this->_addExtra('CS_BAR_SHOW_STACK', $i);
    }

    function getCSBarShowPortfolio()
    {
        $retour = '0';
        if ($this->_issetExtra('CS_BAR_SHOW_PORTFOLIO')) {
            $retour = $this->_getExtra('CS_BAR_SHOW_PORTFOLIO');
        }
        return $retour;
    }

    function setCSBarShowPortfolio($i)
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
            and $setCSBarConnection != '-1'
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

    function getPortletShowActiveRoomList()
    {
        $retour = true;
        if ($this->_issetExtra('PORTLET_ACTIVE_ROOM_LIST')) {
            if ($this->_getExtra('PORTLET_ACTIVE_ROOM_LIST') == '-1') {
                $retour = false;
            }
        }
        return $retour;
    }

    function setPortletShowActiveRoomList()
    {
        $this->_addExtra('PORTLET_ACTIVE_ROOM_LIST', '1');
    }

    function unsetPortletShowActiveRoomList()
    {
        $this->_addExtra('PORTLET_ACTIVE_ROOM_LIST', '-1');
    }

    function getPortletActiveRoomCount()
    {
        $retour = 4;
        if ($this->_issetExtra('PORTLET_ACTIVE_ROOM_COUNT')) {
            $retour = $this->_getExtra('PORTLET_ACTIVE_ROOM_COUNT');
        }
        return $retour;
    }

    function setPortletActiveRoomCount($i)
    {
        $this->_addExtra('PORTLET_ACTIVE_ROOM_COUNT', $i);
    }


    function setPortletShowSearchBox()
    {
        $this->_addExtra('PORTLET_SHOW_SEARCH_BOX', '1');
    }

    function unsetPortletShowSearchBox()
    {
        $this->_addExtra('PORTLET_SHOW_SEARCH_BOX', '-1');
    }

    function getPortletShowSearchBox()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_SEARCH_BOX')) {
            if ($this->_getExtra('PORTLET_SHOW_SEARCH_BOX') == '1') {
                $retour = true;
            }
        }
        return $retour;
    }

    function setPortletShowRoomWideSearchBox()
    {
        $this->_addExtra('PORTLET_SHOW_ROOMWIDE_SEARCH_BOX', '1');
    }

    function unsetPortletShowRoomWideSearchBox()
    {
        $this->_addExtra('PORTLET_SHOW_ROOMWIDE_SEARCH_BOX', '-1');
    }

    function getPortletShowRoomWideSearchBox()
    {
        $retour = true;
        if ($this->_issetExtra('PORTLET_SHOW_ROOMWIDE_SEARCH_BOX')) {
            if ($this->_getExtra('PORTLET_SHOW_ROOMWIDE_SEARCH_BOX') == '-1') {
                $retour = false;
            }
        }
        return $retour;
    }


    function setPortletShowWeatherBox()
    {
        $this->_addExtra('PORTLET_SHOW_WEATHER_BOX', '1');
    }

    function unsetPortletShowWeatherBox()
    {
        $this->_addExtra('PORTLET_SHOW_WEATHER_BOX', '-1');
    }

    function getPortletShowWeatherBox()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_WEATHER_BOX')) {
            if ($this->_getExtra('PORTLET_SHOW_WEATHER_BOX') == '1') {
                $retour = true;
            }
        }
        return $retour;
    }


    function getPortletWeatherLocation()
    {
        $retour = '';
        if ($this->_issetExtra('PORTLET_WEATHER_LOCATION')) {
            $retour = $this->_getExtra('PORTLET_WEATHER_LOCATION');
        }
        return $retour;
    }

    function setPortletWeatherLocation($account)
    {
        $this->_addExtra('PORTLET_WEATHER_LOCATION', $account);
    }


    function setPortletShowDokuverserBox()
    {
        $this->_addExtra('PORTLET_SHOW_DOKUVERSER_BOX', '1');
    }

    function unsetPortletShowDokuverserBox()
    {
        $this->_addExtra('PORTLET_SHOW_DOKUVERSER_BOX', '-1');
    }

    function getPortletShowDokuverserBox()
    {
        $retour = true;
        if ($this->_issetExtra('PORTLET_SHOW_DOKUVERSER_BOX')) {
            if ($this->_getExtra('PORTLET_SHOW_DOKUVERSER_BOX') == '-1') {
                $retour = false;
            }
        }
        return $retour;
    }


    function setPortletShowClockBox()
    {
        $this->_addExtra('PORTLET_SHOW_CLOCK_BOX', '1');
    }

    function unsetPortletShowClockBox()
    {
        $this->_addExtra('PORTLET_SHOW_CLOCK_BOX', '-1');
    }

    function getPortletShowClockBox()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_CLOCK_BOX')) {
            if ($this->_getExtra('PORTLET_SHOW_CLOCK_BOX') == '1') {
                $retour = true;
            }
        }
        return $retour;
    }


    function setPortletShowBuzzwordBox()
    {
        $this->_addExtra('PORTLET_SHOW_BUZZWORD_BOX', '1');
    }

    function unsetPortletShowBuzzwordBox()
    {
        $this->_addExtra('PORTLET_SHOW_BUZZWORD_BOX', '-1');
    }

    function getPortletShowBuzzwordBox()
    {
        $retour = true;
        if ($this->_issetExtra('PORTLET_SHOW_BUZZWORD_BOX')) {
            if ($this->_getExtra('PORTLET_SHOW_BUZZWORD_BOX') == '-1') {
                $retour = false;
            }
        }
        return $retour;
    }


    function setPortletShowConfigurationBox()
    {
        $this->_addExtra('PORTLET_SHOW_CONFIGURATION_BOX', '1');
    }

    function unsetPortletShowConfigurationBox()
    {
        $this->_addExtra('PORTLET_SHOW_CONFIGURATION_BOX', '-1');
    }

    function getPortletShowConfigurationBox()
    {
        $retour = true;
        if ($this->_issetExtra('PORTLET_SHOW_CONFIGURATION_BOX')) {
            if ($this->_getExtra('PORTLET_SHOW_CONFIGURATION_BOX') == '-1') {
                $retour = false;
            }
        }
        return $retour;
    }


    function setPortletShowTwitter()
    {
        $this->_addExtra('PORTLET_SHOW_TWITTER', '1');
    }

    function unsetPortletShowTwitter()
    {
        $this->_addExtra('PORTLET_SHOW_TWITTER', '-1');
    }

    function getPortletShowTwitter()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_TWITTER')) {
            if ($this->_getExtra('PORTLET_SHOW_TWITTER') == '1') {
                $retour = true;
            }
        }
        return $retour;
    }

    function getPortletTwitterAccount()
    {
        $retour = '';
        if ($this->_issetExtra('PORTLET_TWITTER_ACCOUNT')) {
            $retour = $this->_getExtra('PORTLET_TWITTER_ACCOUNT');
        }
        return $retour;
    }

    function setPortletTwitterAccount($account)
    {
        $this->_addExtra('PORTLET_TWITTER_ACCOUNT', $account);
    }


    function setPortletShowYouTube()
    {
        $this->_addExtra('PORTLET_SHOW_YOUTUBE', '1');
    }

    function unsetPortletShowYouTube()
    {
        $this->_addExtra('PORTLET_SHOW_YOUTUBE', '-1');
    }

    function getPortletShowYouTube()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_YOUTUBE')) {
            if ($this->_getExtra('PORTLET_SHOW_YOUTUBE') == '1') {
                $retour = true;
            }
        }
        return $retour;
    }

    function getPortletYouTubeAccount()
    {
        $retour = '';
        if ($this->_issetExtra('PORTLET_YOUTUBE_ACCOUNT')) {
            $retour = $this->_getExtra('PORTLET_YOUTUBE_ACCOUNT');
        }
        return $retour;
    }

    function setPortletYouTubeAccount($account)
    {
        $this->_addExtra('PORTLET_YOUTUBE_ACCOUNT', $account);
    }


    function setPortletShowFlickr()
    {
        $this->_addExtra('PORTLET_SHOW_FLICKR', '1');
    }

    function unsetPortletShowFlickr()
    {
        $this->_addExtra('PORTLET_SHOW_FLICKR', '-1');
    }

    function getPortletShowFlickr()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_FLICKR')) {
            if ($this->_getExtra('PORTLET_SHOW_FLICKR') == '1') {
                $retour = true;
            }
        }
        return $retour;
    }

    function getPortletFlickrID()
    {
        $retour = '';
        if ($this->_issetExtra('PORTLET_FLICKR_ID')) {
            $retour = $this->_getExtra('PORTLET_FLICKR_ID');
        }
        return $retour;
    }

    function setPortletFlickrID($id)
    {
        $this->_addExtra('PORTLET_FLICKR_ID', $id);
    }


    function setPortletShowRSS()
    {
        $this->_addExtra('PORTLET_SHOW_RSS', '1');
    }

    function unsetPortletShowRSS()
    {
        $this->_addExtra('PORTLET_SHOW_RSS', '-1');
    }

    function getPortletShowRSS()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_RSS')) {
            if ($this->_getExtra('PORTLET_SHOW_RSS') == '1') {
                $retour = true;
            }
        }
        return $retour;
    }

    function setPortletRSSArray($array)
    {
        $this->_addExtra('PORTLET_RSS_ARRAY', $array);
    }


    function getPortletRSSArray()
    {
        $retour = array();
        if ($this->_issetExtra('PORTLET_RSS_ARRAY')) {
            $retour = $this->_getExtra('PORTLET_RSS_ARRAY');
        }
        return $retour;
    }


    function setPortletShowNewItemBox()
    {
        $this->_addExtra('PORTLET_SHOW_NEW_ITEM', '1');
    }

    function unsetPortletShowNewItemBox()
    {
        $this->_addExtra('PORTLET_SHOW_NEW_ITEM', '-1');
    }

    function getPortletShowNewItemBox()
    {
        $retour = true;
        if ($this->_issetExtra('PORTLET_SHOW_NEW_ITEM')) {
            if ($this->_getExtra('PORTLET_SHOW_NEW_ITEM') == '-1') {
                $retour = false;
            }
        }
        return $retour;
    }

    function setPortletShowNoteBox()
    {
        $this->_addExtra('PORTLET_SHOW_NOTE', '1');
    }

    function unsetPortletShowNoteBox()
    {
        $this->_addExtra('PORTLET_SHOW_NOTE', '-1');
    }

    function getPortletShowNoteBox()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_NOTE')) {
            if ($this->_getExtra('PORTLET_SHOW_NOTE') == '1') {
                $retour = true;
            }
        }
        return $retour;
    }

    function setPortletNoteContent($content)
    {
        $this->_addExtra('PORTLET_NOTE_CONTENT', $content);
    }

    function getPortletNoteContent()
    {
        $retour = '';
        if ($this->_issetExtra('PORTLET_NOTE_CONTENT')) {
            $retour = $this->_getExtra('PORTLET_NOTE_CONTENT');
        }
        return $retour;
    }

    function setPortletShowReleasedEntriesBox()
    {
        $this->_addExtra('PORTLET_SHOW_RELEASED_ENTRIES', '1');
    }

    function unsetPortletShowReleasedEntriesBox()
    {
        $this->_addExtra('PORTLET_SHOW_RELEASED_ENTRIES', '-1');
    }

    function getPortletShowReleasedEntriesBox()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_RELEASED_ENTRIES')) {
            if ($this->_getExtra('PORTLET_SHOW_RELEASED_ENTRIES') == '1') {
                $retour = true;
            }
        }
        return $retour;
    }

    function setPortletShowTagBox()
    {
        $this->_addExtra('PORTLET_SHOW_TAG', '1');
    }

    function unsetPortletShowTagBox()
    {
        $this->_addExtra('PORTLET_SHOW_TAG', '-1');
    }

    function getPortletShowTagBox()
    {
        $retour = false;
        if ($this->_issetExtra('PORTLET_SHOW_TAG')) {
            if ($this->_getExtra('PORTLET_SHOW_TAG') == '1') {
                $retour = true;
            }
        }
        return $retour;
    }

    function setHomeConfig($column_array)
    {
        $this->_addExtra('HOME_CONFIG', $column_array);
    }

    function getHomeConfig()
    {
        $retour = array();
        if ($this->_issetExtra('HOME_CONFIG')) {
            $retour = $this->_getExtra('HOME_CONFIG');
        }
        return $retour;
    }

    function setMyroomConfig($column_array)
    {
        $this->_addExtra('MYROOM_CONFIG', $column_array);
    }

    function getMyroomConfig()
    {
        $retour = array();
        if ($this->_issetExtra('MYROOM_CONFIG')) {
            $retour = $this->_getExtra('MYROOM_CONFIG');
        }
        return $retour;
    }

    function updateHomeConfiguration($column_array)
    {
        $portlet_array = array();
        foreach ($column_array as $column) {
            foreach ($column as $column_entry) {
                if (($column_entry != 'null') && ($column_entry != 'empty')) {
                    $portlet_array[] = $column_entry;
                }
            }
        }
        if (in_array('cs_privateroom_home_buzzword_view', $portlet_array)) {
            $this->setPortletShowBuzzwordBox();
        } else {
            $this->unsetPortletShowBuzzwordBox();
        }

        if (in_array('cs_privateroom_home_configuration_view', $portlet_array)) {
            $this->setPortletShowConfigurationBox();
        } else {
            $this->unsetPortletShowConfigurationBox();
        }

        if (in_array('cs_privateroom_home_clock_view', $portlet_array)) {
            $this->setPortletShowClockBox();
        } else {
            $this->unsetPortletShowClockBox();
        }

        if (in_array('cs_privateroom_home_rss_ticker_view', $portlet_array)) {
            $this->setPortletShowRSS();
        } else {
            $this->unsetPortletShowRSS();
        }

        if (in_array('cs_privateroom_home_dokuverser_view', $portlet_array)) {
            $this->setPortletShowDokuverserBox();
        } else {
            $this->unsetPortletShowDokuverserBox();
        }

        if (in_array('cs_privateroom_home_twitter_view', $portlet_array)) {
            $this->setPortletShowTwitter();
        } else {
            $this->unsetPortletShowTwitter();
        }

        if (in_array('cs_privateroom_home_youtube_view', $portlet_array)) {
            $this->setPortletShowYouTube();
        } else {
            $this->unsetPortletShowYouTube();
        }

        if (in_array('cs_privateroom_home_flickr_view', $portlet_array)) {
            $this->setPortletShowFlickr();
        } else {
            $this->unsetPortletShowFlickr();
        }

        if (in_array('cs_privateroom_home_room_view', $portlet_array)) {
            $this->setPortletShowActiveRoomList();
        } else {
            $this->unsetPortletShowActiveRoomList();
        }

        if (in_array('cs_privateroom_home_new_entries_view', $portlet_array)) {
            $this->setPortletShowNewEntryList();
        } else {
            $this->unsetPortletShowNewEntryList();
        }

        if (in_array('cs_privateroom_home_weather_view', $portlet_array)) {
            $this->setPortletShowWeatherBox();
        } else {
            $this->unsetPortletShowWeatherBox();
        }

        if (in_array('cs_privateroom_home_search_view', $portlet_array)) {
            $this->setPortletShowSearchBox();
        } else {
            $this->unsetPortletShowSearchBox();
        }

        if (in_array('cs_privateroom_home_roomwide_search_view', $portlet_array)) {
            $this->setPortletShowRoomWideSearchBox();
        } else {
            $this->unsetPortletShowRoomWideSearchBox();
        }

        if (in_array('cs_privateroom_home_new_item_view', $portlet_array)) {
            $this->setPortletShowNewItemBox();
        } else {
            $this->unsetPortletShowNewItemBox();
        }

        if (in_array('cs_privateroom_home_note_view', $portlet_array)) {
            $this->setPortletShowNoteBox();
        } else {
            $this->unsetPortletShowNoteBox();
        }

        if (in_array('cs_privateroom_home_released_entries_view', $portlet_array)) {
            $this->setPortletShowReleasedEntriesBox();
        } else {
            $this->unsetPortletShowReleasedEntriesBox();
        }

        if (in_array('cs_privateroom_home_tag_view', $portlet_array)) {
            $this->setPortletShowTagBox();
        } else {
            $this->unsetPortletShowTagBox();
        }
    }

    function setMyroomDisplayConfig($myroom_array)
    {
        $this->_addExtra('MYROOM_DISPLAY_CONFIG', $myroom_array);
    }

    function getMyroomDisplayConfig()
    {
        $retour = array();
        if ($this->_issetExtra('MYROOM_DISPLAY_CONFIG')) {
            $retour = $this->_getExtra('MYROOM_DISPLAY_CONFIG');
        }
        return $retour;
    }

    function issetMyroomDisplayConfig()
    {
        return $this->_issetExtra('MYROOM_DISPLAY_CONFIG');
    }

    function setMyEntriesDisplayConfig($my_entries_array)
    {
        $this->_addExtra('MY_ENTRIES_DISPLAY_CONFIG', $my_entries_array);
    }

    function getMyEntriesDisplayConfig()
    {
        $retour = array();
        if ($this->_issetExtra('MY_ENTRIES_DISPLAY_CONFIG')) {
            $retour = $this->_getExtra('MY_ENTRIES_DISPLAY_CONFIG');
        }
        return $retour;
    }

    function setMyCalendarDisplayConfig($my_calendar_array)
    {
        $this->_addExtra('MY_CALENDAR_DISPLAY_CONFIG', $my_calendar_array);
    }

    function getMyCalendarDisplayConfig()
    {
        $retour = array();
        if ($this->_issetExtra('MY_CALENDAR_DISPLAY_CONFIG')) {
            $retour = $this->_getExtra('MY_CALENDAR_DISPLAY_CONFIG');
        }
        return $retour;
    }

    /* END OF PORTLET FUNCTIONS
     * *****************
     */

    function setEmailToCommSy()
    {
        $this->_addExtra('EMAIL_TO_COMMSY', '1');
    }

    function unsetEmailToCommSy()
    {
        $this->_addExtra('EMAIL_TO_COMMSY', '-1');
    }

    function getEmailToCommSy()
    {
        $retour = false;
        if ($this->_issetExtra('EMAIL_TO_COMMSY')) {
            if ($this->_getExtra('EMAIL_TO_COMMSY') == '1') {
                $retour = true;
            }
        }
        return $retour;
    }


    function setEmailToCommSySecret($value)
    {
        $this->_addExtra('EMAIL_TO_COMMSY_SECRET', $value);
    }

    function getEmailToCommSySecret()
    {
        $retour = false;
        if ($this->_issetExtra('EMAIL_TO_COMMSY_SECRET')) {
            $retour = $this->_getExtra('EMAIL_TO_COMMSY_SECRET');
        }
        return $retour;
    }


    function setDashboardLayout($data)
    {
        $this->_addExtra('DASHBOARD_LAYOUT', $data);
    }

    function getDashboardLayout()
    {
        $retour = false;
        if ($this->_issetExtra('DASHBOARD_LAYOUT')) {
            return $this->_getExtra('DASHBOARD_LAYOUT');
        }
        return $retour;
    }

    function usersCanSetExternalCalendarsUrl()
    {
        return true;
    }

    function setCalendarSelection($data)
    {
        $this->_addExtra('CALENDAR_SELECTION', $data);
    }

    function getCalendarSelection()
    {
        $retour = false;
        if ($this->_issetExtra('CALENDAR_SELECTION')) {
            return $this->_getExtra('CALENDAR_SELECTION');
        }
        return $retour;
    }
}
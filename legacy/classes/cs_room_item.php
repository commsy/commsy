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

/* upper class of the project item
 */

use App\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;

/** father class for a rooms (project or community)
 * this class implements an abstract room item.
 */
class cs_room_item extends cs_context_item
{
    public $_old_status = null;

    /** constructor.
     *
     * @param object environment environment of the commsy project
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
    }

    // #####################################################
    // methods for linking times (clock pulses) and rooms #
    // #####################################################

    public function _getContinuousStatus()
    {
        $retour = $this->_getValue('continuous');
        if (empty($retour)) {
            $retour = -1;
        }

        return $retour;
    }

    public function isContinuous()
    {
        $retour = false;
        $value = $this->_getContinuousStatus();
        if (1 == $value) {
            $retour = true;
        }

        return $retour;
    }

    public function setContinuous()
    {
        $this->_setValue('continuous', 1, true);
        $this->_setLinksToTimeLabels();
    }

    public function setNotContinuous()
    {
        $this->_setValue('continuous', -1, true);
    }

    public function _setLinksToTimeLabels()
    {
        $portal_item = $this->getContextItem();
        $start_date = $this->_getDateFromDateTime($this->getCreationDate());
        if ($this->isClosed()) {
            $end_date = $this->_getDateFromDateTime($this->getClosureDate());
        }

        $current_date = getCurrentDate();

        $get_time_item_ids = false;
        $first = true;

        if ($portal_item->showTime()) {
            $time_item_id_array = [];
            $time_list = $portal_item->getTimeList();
            if ($time_list) {
                $time_item = $time_list->getFirst();
                while ($time_item) {
                    if (!$time_item->isDeleted()) {
                        $date_label_start = $this->_getBeginDateFromTimeLabel($time_item->getTitle());
                        $date_label_end = $this->_getEndDateFromTimeLabel($time_item->getTitle());
                        if ($date_label_end < $date_label_start) {
                            $date_label_end = $date_label_end + 10000;
                        }
                        if ($date_label_start <= $start_date
                             and $start_date <= $date_label_end
                        ) {
                            $get_time_item_ids = true;
                        }
                        if ($first) {
                            if ($date_label_start > $start_date) {
                                $get_time_item_ids = true;
                            }
                            $first = false;
                        }
                        if ($current_date < $date_label_start) {
                            $get_time_item_ids = false;
                        }
                        if (isset($end_date)
                             and $date_label_start <= $end_date
                             and $end_date <= $date_label_end) {
                            $get_time_item_ids = false;
                        }
                        if ($get_time_item_ids) {
                            $time_item_id_array[] = $time_item->getItemID();
                        }
                    }
                    $time_item = $time_list->getNext();
                }
            }
            $this->setTimeListByID($time_item_id_array);
        }
    }

    public function _getDateFromDateTime($datetime)
    {
        $retour = '';
        if (!empty($datetime)) {
            $retour = $datetime[0].$datetime[1].$datetime[2].$datetime[3].$datetime[5].$datetime[6].$datetime[8].$datetime[9];
        }

        return $retour;
    }

    public function _getBeginDateFromTimeLabel($title)
    {
        $retour = '';
        $title_array = explode('_', (string) $title);
        $day_month = $this->_getBeginDayMonthFromTimeLabel($title);
        if (isset($title_array[0])
             and isset($day_month[0])
             and isset($day_month[1])
             and isset($day_month[3])
             and isset($day_month[4])
        ) {
            $retour = $title_array[0].$day_month[3].$day_month[4].$day_month[0].$day_month[1];
        }

        return $retour;
    }

    public function _getEndDateFromTimeLabel($title)
    {
        $retour = '';
        $title_array = explode('_', (string) $title);
        $day_month = $this->_getEndDayMonthFromTimeLabel($title);
        if (isset($title_array[0])
             and isset($day_month[0])
             and isset($day_month[1])
             and isset($day_month[3])
             and isset($day_month[4])
        ) {
            $retour = $title_array[0].$day_month[3].$day_month[4].$day_month[0].$day_month[1];
        }

        return $retour;
    }

    public function _getDayMonthFromTimeLabel($title, $key)
    {
        $portal_item = $this->getContextItem();
        if (!$portal_item->isPortal()) {
            $portal_item = $this->_environment->getCurrentPortalItem();
        }
        $time_text_array = $portal_item->getTimeTextArray();
        $title_array = explode('_', (string) $title);
        $retour = $time_text_array[$title_array[1]][$key];

        return $retour;
    }

    public function _getBeginDayMonthFromTimeLabel($title)
    {
        return $this->_getDayMonthFromTimeLabel($title, 'BEGIN');
    }

    public function _getEndDayMonthFromTimeLabel($title)
    {
        return $this->_getDayMonthFromTimeLabel($title, 'END');
    }

    public function setClosureDate($value)
    {
        $this->_addExtra('CLOSURE_DATE', $value);
    }

    public function getClosureDate()
    {
        $retour = '';
        if ($this->_issetExtra('CLOSURE_DATE')) {
            $retour = $this->_getExtra('CLOSURE_DATE');
        }

        return $retour;
    }

    public function setContactPerson($fullname)
    {
        if (!empty($fullname)) {
            $value = $this->_getValue('contact_persons');
            if (!mb_stristr((string) $value, (string) $fullname)) {
                $value .= $fullname.', ';
                $this->_setValue('contact_persons', $value);
            }
        }
    }

    public function getContactPersonString()
    {
        $return = trim((string) $this->_getValue('contact_persons'));
        if (!empty($return)
             and mb_strstr($return, ',')
             and ',' == mb_substr($return, mb_strlen($return) - 1)
        ) {
            $return = mb_substr($return, 0, mb_strlen($return) - 1);
        }

        return $return;
    }

    public function emptyContactPersonString()
    {
        $this->_unsetValue('contact_persons');
    }

    public function renewContactPersonString()
    {
        $this->emptyContactPersonString();
        $moderator_list = $this->getContactModeratorList();
        $current_moderator = $moderator_list->getFirst();
        while ($current_moderator) {
            $contact_name = $current_moderator->getFullname();
            if (!empty($contact_name)
                 and 'GUEST' != mb_strtoupper((string) $contact_name)
            ) {
                $this->setContactPerson($contact_name);
            }
            $current_moderator = $moderator_list->getNext();
        }
        $this->setChangeModificationOnSave(false);
        $this->save();
    }

    public function renewDescription()
    {
        if ($this->_issetExtra('DESCRIPTION')) {
            $this->setDescriptionArray($this->_getExtra('DESCRIPTION'));
            $this->_unsetExtra('DESCRIPTION');
        } else {
            $description_array = $this->getDescriptionArray();
            if (empty($description_array)) {
                $this->setDescriptionArray([]);
            }
        }
        $this->setChangeModificationOnSave(false);
        $this->save();
    }

    /** close a room
     * this method sets the status of the room to closed.
     */
    public function close(): void
    {
        $this->setClosureDate(getCurrentDateTimeInMySQL());
        parent::close();
    }

    public function delete()
    {
        // delete associated annotations
        $this->deleteAssociatedAnnotations();
    }

    /** get time of a room
     * this method returns a list of clock pulses which are linked to the room.
     *
     * @return object cs_list a list of clock pulses (cs_label_item)
     */
    public function getTimeList()
    {
        $time_list = $this->_getLinkedTimeItems($this->_environment->getTimeManager(), 'in_time');
        $time_list->sortBy('sorting');

        return $time_list;
    }

    /** get list of linked items
     * this method returns a list of items which are linked to the news item.
     *
     * @return object cs_list a list of cs_items
     *
     * @author CommSy Development Group
     */
    public function _getLinkedTimeItems($item_manager, $link_type, $order = '')
    {
        if (!isset($this->_data[$link_type]) or !is_object($this->_data[$link_type])) {
            global $environment;
            $link_manager = $environment->getLinkManager();
            $link_manager->setItemIDLimit($this->getItemID());
            // preliminary version: there should be something like 'getIDArray() in the link_manager'

            $id_array = [];
            $link_array = $link_manager->getLinks($link_type, $this, $this->getVersionID(), 'eq');
            $id_array = [];
            foreach ($link_array as $link) {
                if ($link['to_item_id'] == $this->getItemID()) {
                    $id_array[] = $link['from_item_id'];
                } elseif ($link['from_item_id'] == $this->getItemID()) {
                    $id_array[] = $link['to_item_id'];
                }
            }
            $this->_data[$link_type] = $item_manager->getItemList($id_array);
        }

        return $this->_data[$link_type];
    }

    /** set clock pulses of a room item by id
     * this method sets a list of clock pulses item_ids which are linked to the room.
     *
     * @param array of time ids
     */
    public function setTimeListByID($value)
    {
        $time_array = [];
        foreach ($value as $iid) {
            $tmp_data = [];
            $tmp_data['iid'] = $iid;
            $time_array[] = $tmp_data;
        }
        $this->_setValue('in_time', $time_array, false);
    }

    /******************************************
     *  diese Funktion wird in der configuration_preferences verwendet,
     *  weil die obige aufgrund eines PHP-Bugs mehrmals aufgerufen wird
     *  und dies zu einer zeitlichen Verzögerung von 30 Sekunden kommt
     *
     *  Datum:  20.09.2013
     *  Autor:  Iver Jackewitz
     *  Kernel: Linux RZ-CS-WEB01 3.2.0-53-virtual #81-Ubuntu SMP Thu Aug 22 21:21:26 UTC 2013 x86_64
     *  PHP:    PHP Version 5.3.10-1ubuntu3.8
     *
     *  völlig unerklärlich
     */
    public function setTimeListByID2($value)
    {
        $time_array = [];
        foreach ($value as $iid) {
            $tmp_data = [];
            $tmp_data['iid'] = $iid;
            $time_array[] = $tmp_data;
        }
        $this->_setValue('in_time', $time_array, false);
    }

    /** set clock pulses of a room
     * this method sets a list of clock pulses which are linked to the room.
     *
     * @param object cs_list value list of clock pulses (cs_label_item)
     */
    public function setTimeList($value)
    {
        $this->_setObject('in_time', $value, false);
    }

    // #####################################################
    // methods for template technique                     #
    // #####################################################

    public function _getTemplateStatus()
    {
        $retour = '-1';
        $value = $this->_getValue('template');
        if (!empty($value) and 1 == $value) {
            $retour = 1;
        }

        return $retour;
    }

    public function isTemplate()
    {
        $retour = false;
        $value = $this->_getTemplateStatus();
        if (1 == $value) {
            $retour = true;
        }

        return $retour;
    }

    public function setTemplate()
    {
        $this->_setValue('template', 1, true);
    }

    public function setNotTemplate()
    {
        $this->_setValue('template', -1, true);
    }

    /** get topics of a project
     * this method returns a list of topics which are linked to the project.
     *
     * @return object cs_list a list of topics (cs_label_item)
     */
    public function getTopicList()
    {
        $topic_list = $this->_getLinkedItems($this->_environment->getLabelManager(), CS_TOPIC_TYPE);
        $topic_list->sortBy('name');

        return $topic_list;
    }

    /** set topics of a project item by id
     * this method sets a list of topic item_ids which are linked to the project.
     *
     * @param array of topic ids
     *
     * @author CommSy Development Group
     */
    public function setTopicListByID($value)
    {
        $topic_array = [];
        foreach ($value as $iid) {
            $tmp_data = [];
            $tmp_data['iid'] = $iid;
            $topic_array[] = $tmp_data;
        }
        $this->_setValue(CS_TOPIC_TYPE, $topic_array, false);
    }

    /** set topics of a project
     * this method sets a list of topics which are linked to the project.
     *
     * @param object cs_list value list of topics (cs_label_item)
     *
     * @author CommSy Development Group
     */
    public function setTopicList($value)
    {
        $this->_setObject(CS_TOPIC_TYPE, $value, false);
    }

    /** get materials of a project
     * this method returns a list of materials which are linked to the project.
     *
     * @return object cs_list a list of materials (cs_material_item)
     *
     * @author CommSy Development Group
     */
    public function getMaterialList()
    {
        return $this->_getLinkedItems($this->_environment->getMaterialManager(), CS_MATERIAL_TYPE);
    }

    /** set materials of a project item by item id and version id
     * this method sets a list of material item_ids and version_ids which are linked to the project.
     *
     * @param array of material ids, index of id must be 'iid', index of version must be 'vid'
     * Example:
     * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
     *
     * @author CommSy Development Group
     */
    public function setMaterialListByID($value)
    {
        $this->setLinkedItemsByID(CS_MATERIAL_TYPE, $value);
    }

    /** set materials of a project
     * this method sets a list of materials which are linked to the project.
     *
     * @param string value title of the project
     *
     * @author CommSy Development Group
     */
    public function setMaterialList($value)
    {
        $this->_setObject(CS_MATERIAL_TYPE, $value, false);
    }

    /** Sets the data of the item.
     *
     * @param $data_array Is the prepared array from "_buildItem($db_array)"
     *
     * @return bool TRUE if data is valid FALSE otherwise
     */
    public function _setItemData($data_array): void
    {
        $this->_data = $data_array;
        $retour = $this->isValid();
        if ($retour) {
            $this->_old_status = $this->getStatus();
        }
    }

    // ###############################################################
    // mail to moderation, if the room status changed
    // - delete
    // - undelete
    // - open
    // - archive
    // - template (not implemented yet because flagged function)
    // - untemplate (not implemented yet because flagged function)
    // - reopen
    // - link to and unlink from community room
    // ###############################################################

    public function _sendMailRoomDeleteToProjectModeration()
    {
        $this->_sendMailToModeration('project', 'delete');
    }

    public function _sendMailRoomDeleteToCommunityModeration()
    {
        $this->_sendMailToModeration('community', 'delete');
    }

    public function _sendMailRoomDeleteToPortalModeration()
    {
        $this->_sendMailToModeration('portal', 'delete');
    }

    public function _sendMailRoomUnDeleteToProjectModeration()
    {
        $this->_sendMailToModeration('project', 'undelete');
    }

    public function _sendMailRoomUnDeleteToCommunityModeration()
    {
        $this->_sendMailToModeration('community', 'undelete');
    }

    public function _sendMailRoomUnDeleteToPortalModeration(): void
    {
        $this->_sendMailToModeration('portal', 'undelete');
    }

    public function _sendMailRoomOpenToProjectModeration(): void
    {
        $this->_sendMailToModeration('project', 'open');
    }

    public function _sendMailRoomOpenToCommunityModeration(): void
    {
        $this->_sendMailToModeration('community', 'open');
    }

    public function _sendMailRoomOpenToPortalModeration(): void
    {
        $this->_sendMailToModeration('portal', 'open');
    }

    public function _sendMailRoomArchiveToProjectModeration()
    {
        $this->_sendMailToModeration('project', 'archive');
    }

    public function _sendMailRoomArchiveToCommunityModeration()
    {
        $this->_sendMailToModeration('community', 'archive');
    }

    public function _sendMailRoomArchiveToPortalModeration()
    {
        $this->_sendMailToModeration('portal', 'archive');
    }

    public function _sendMailRoomReOpenToProjectModeration()
    {
        $this->_sendMailToModeration('project', 'reopen');
    }

    public function _sendMailRoomReOpenToCommunityModeration()
    {
        $this->_sendMailToModeration('community', 'reopen');
    }

    public function _sendMailRoomReOpenToPortalModeration()
    {
        $this->_sendMailToModeration('portal', 'reopen');
    }

    public function _sendMailRoomLinkToProjectModeration()
    {
        $this->_sendMailToModeration('project', 'link');
    }

    public function _sendMailRoomLinkToCommunityModeration()
    {
        $this->_sendMailToModeration('community', 'link');
    }

    public function _sendMailRoomLinkToPortalModeration()
    {
        $this->_sendMailToModeration('portal', 'link');
    }

    public function _sendMailRoomLockToProjectModeration()
    {
        $this->_sendMailToModeration('project', 'lock');
    }

    public function _sendMailRoomLockToCommunityModeration()
    {
        $this->_sendMailToModeration('community', 'lock');
    }

    public function _sendMailRoomLockToPortalModeration()
    {
        $this->_sendMailToModeration('portal', 'lock');
    }

    public function _sendMailRoomUnlockToProjectModeration()
    {
        $this->_sendMailToModeration('project', 'unlock');
    }

    public function _sendMailRoomUnlockToCommunityModeration()
    {
        $this->_sendMailToModeration('community', 'unlock');
    }

    public function _sendMailRoomUnlockToPortalModeration()
    {
        $this->_sendMailToModeration('portal', 'unlock');
    }

    /** get UsageInfos
     *   this method returns the usage infos.
     *
     *   @return array
     */
    public function getUsageInfoArray()
    {
        $retour = null;

        if (('false' == $this->_getExtra('USAGE_INFO_GLOBAL')) or (!$this->_issetExtra('USAGE_INFO_GLOBAL'))) {
            if ($this->_issetExtra('USAGE_INFO')) {
                $retour = $this->_getExtra('USAGE_INFO');
                if (empty($retour)) {
                    $retour = [];
                } elseif (!is_array($retour)) {
                    $retour = XML2Array($retour);
                }
            } else {
                $retour = [];
            }
        } else {
            $retour = [];
            $array = $this->_default_rubrics_array;
            foreach ($array as $current) {
                $retour[] = $current.'_no';
            }
            $retour[] = 'home_no';
        }

        return $retour;
    }

    /** set UsageInfos
     *   this method sets the usage infos.
     *
     *   @param array
     */
    public function setUsageInfoArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO', $value_array);
        }
    }

    public function setUsageInfoGlobal($value)
    {
        $this->_addExtra('USAGE_INFO_GLOBAL', $value);
    }

    /** get UsageInfos
     *   this method returns the usage infos.
     *
     *   @return array
     */
    public function getUsageInfoFormArray()
    {
        $retour = null;
        if (('false' == $this->_getExtra('USAGE_INFO_GLOBAL')) or (!$this->_issetExtra('USAGE_INFO_GLOBAL'))) {
            if ($this->_issetExtra('USAGE_INFO_FORM')) {
                $retour = $this->_getExtra('USAGE_INFO_FORM');
                if (empty($retour)) {
                    $retour = [];
                } elseif (!is_array($retour)) {
                    $retour = XML2Array($retour);
                }
            } else {
                $retour = [];
            }
        } else {
            $retour = [];
            $array = $this->_default_rubrics_array;
            foreach ($array as $current) {
                $retour[] = $current.'_no';
            }
            $retour[] = 'home_no';
        }

        return $retour;
    }

    /** set UsageInfos
     *  this method sets the usage infos.
     *
     * @param array
     */
    public function setUsageInfoFormArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_FORM', $value_array);
        }
    }

    public function getUsageInfoHeaderArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_HEADER');
            if (empty($retour)) {
                $retour = [];
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = [];
        }

        return $retour;
    }

    public function setUsageInfoHeaderArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_HEADER', $value_array);
        }
    }

    public function getUsageInfoFormHeaderArray()
    {
        $retour = null;
        if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_FORM_HEADER');
            if (empty($retour)) {
                $retour = [];
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = [];
        }

        return $retour;
    }

    public function setUsageInfoFormHeaderArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_FORM_HEADER', $value_array);
        }
    }

    public function getUsageInfoTextArray()
    {
        $retour = null;
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

        return $retour;
    }

    public function setUsageInfoTextArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_TEXT', $value_array);
        }
    }

    public function getUsageInfoFormTextArray()
    {
        $retour = null;
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

        return $retour;
    }

    public function setUsageInfoFormTextArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_FORM_TEXT', $value_array);
        }
    }

    public function getUsageInfoHeaderForRubric($rubric)
    {
        $translator = $this->_environment->getTranslationObject();
        if ($this->_issetExtra('USAGE_INFO_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_HEADER');
            if (empty($retour)) {
                $retour = [];
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = [];
        }
        if (isset($retour[mb_strtoupper((string)$rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper((string)$rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper((string)$rubric, 'UTF-8')];
        } else {
            $retour = $translator->getMessage('USAGE_INFO_HEADER');
        }

        return $retour;
    }

    public function setUsageInfoHeaderForRubric($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_HEADER')) {
            $value_array = $this->_getExtra('USAGE_INFO_HEADER');
            if (empty($value_array)) {
                $value_array = [];
            } elseif (!is_array($value_array)) {
                $value_array = XML2Array($value_array);
            }
        } else {
            $value_array = [];
        }
        $value_array[mb_strtoupper((string)$rubric, 'UTF-8')] = $string;
        $this->_addExtra('USAGE_INFO_HEADER', $value_array);
    }

    public function getUsageInfoHeaderForRubricForm($rubric)
    {
        $translator = $this->_environment->getTranslationObject();
        if ($this->_issetExtra('USAGE_INFO_HEADER')) {
            $retour = $this->_getExtra('USAGE_INFO_HEADER');
            if (empty($retour)) {
                $retour = [];
            } elseif (!is_array($retour)) {
                $retour = XML2Array($retour);
            }
        } else {
            $retour = [];
        }
        if (isset($retour[mb_strtoupper((string)$rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper((string)$rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper((string)$rubric, 'UTF-8')];
        } else {
            $retour = $translator->getMessage('USAGE_INFO_HEADER');
        }

        return $retour;
    }

    public function setUsageInfoHeaderForRubricForm($rubric, $string)
    {
        if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
            $value_array = $this->_getExtra('USAGE_INFO_FORM_HEADER');
            if (empty($value_array)) {
                $value_array = [];
            } elseif (!is_array($value_array)) {
                $value_array = XML2Array($value_array);
            }
        } else {
            $value_array = [];
        }
        $value_array[mb_strtoupper((string)$rubric, 'UTF-8')] = $string;
        $this->_addExtra('USAGE_INFO_FORM_HEADER', $value_array);
    }

    public function initTagRootItem()
    {
        $tag_manager = $this->_environment->getTagManager();
        $tag_root_item = $tag_manager->getRootTagItemFor($this->getItemID());
        if (isset($tag_root_item)) {
            $tag_root_item_id = $tag_root_item->getItemID();
        }
        if (!isset($tag_root_item)
            or empty($tag_root_item_id)
        ) {
            $tag_manager->createRootTagItemFor($this->getItemID());
        }
        unset($tag_root_item);
        unset($tag_manager);
    }

    public function getCountPlugin($plugin, $start, $end)
    {
        $retour = 0;

        $user_manager = $this->_environment->getUserManager();
        $user_manager->resetLimits();
        $user_manager->setContextLimit($this->getItemID());
        $retour = $user_manager->getCountPlugin($plugin, $start, $end);
        unset($user_manager);

        return $retour;
    }

    /** det description array.
     *
     * @return array description text in different languages
     */
    public function getDescriptionArray(): array
    {
        $retour = $this->_getValue('description');
        if (empty($retour)) {
            $retour = [];
        }

        return $retour;
    }

    public function getDescription()
    {
        $retour = $this->_getValue('room_description');
        if (empty($retour)) {
            $retour = '';
        }

        return $retour;
    }

    public function setDescription($value)
    {
        $this->_setValue('room_description', $value);
    }

    /** set description array.
     *
     * @param array value description text in different languages
     */
    public function setDescriptionArray(array $value): void
    {
        $this->_setValue('description', $value);
    }

    /**
     * Get the room's slug (a unique textual identifier for this room).
     */
    public function getSlug(): ?string
    {
        return $this->_getValue('slug') ?: null;
    }

    /**
     * Set the room's slug (a unique textual identifier for this room).
     */
    public function setSlug(?string $slug): void
    {
        $slug = !empty($slug) ? strtolower($slug) : null;

        $this->_setValue('slug', $slug);
    }

    /**
     * Archives a room (which puts it into read-only mode).
     *
     * Subclass implementations of this method may also archive related rooms.
     */
    public function archive(): void
    {
        $this->setArchived(true);

        // remove room from elastic index
        $container = $this->_environment->getSymfonyContainer();
        $objectPersister = $container->get('app.elastica.object_persister.commsy_room');

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(Room::class);

        $this->deleteElasticItem($objectPersister, $repository);
    }

    /**
     * Unarchives a room (which removes the read-only mode restrictions).
     *
     * Subclass implementations of this method may also unarchive related rooms.
     */
    public function unarchive(): void
    {
        $this->setArchived(false);

        /**
         * We mirror the archived flag to the doctrine entity here for now, otherwise the indexable check will prevent
         * the room from being indexed
         * @see Room::isIndexable()
         */
        $container = $this->_environment->getSymfonyContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(Room::class);

        /** @var Room $room */
        $room = $repository->findOneByItemId($this->getItemID());
        $room->setArchived(false);
    }

    public function getArchived(): bool
    {
        return '1' == $this->_getValue('archived');
    }

    public function setArchived(bool $archived): void
    {
        $this->_setValue('archived', $archived ? 1 : 0);
    }

    public function isUsed($start_date, $end_date)
    {
        $retour = false;

        $user_manager = $this->_environment->getUserManager();
        $user_manager->setContextLimit($this->getItemID());
        $count = $user_manager->getCountUsedAccounts($start_date, $end_date);
        unset($user_manager);
        if (!empty($count)
            and is_numeric($count)
            and $count > 0
        ) {
            $retour = true;
        } else {
            $item_manager = $this->_environment->getItemManager();
            $item_manager->setContextLimit($this->getItemID());
            $count = $item_manager->getCountItems($start_date, $end_date);
            if (!empty($count)
                and is_numeric($count)
                and $count > 0
            ) {
                $retour = true;
            }
            unset($item_manager);
        }

        return $retour;
    }

    public function updateElastic(): void
    {
        if ($this->getArchived()) {
            return;
        }

        $container = $this->_environment->getSymfonyContainer();
        $objectPersister = $container->get('app.elastica.object_persister.commsy_room');
        $em = $container->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(Room::class);

        $this->replaceElasticItem($objectPersister, $repository);
    }

    protected function deleteFromElastic(): void
    {
        $container = $this->_environment->getSymfonyContainer();
        $objectPersister = $container->get('app.elastica.object_persister.commsy_room');
        $em = $container->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(Room::class);

        $this->deleteElasticItem($objectPersister, $repository);
    }

    public function saveLastlogin()
    {
        $manager = $this->_environment->getManager($this->getType());
        if ($manager) {
            return $manager->saveLastLogin($this);
        }
    }

    /** get lastlogin of a context
     * this method returns the last login date of the context.
     *
     * @return string lastlogin of a context
     */
    public function getLastLogin()
    {
        return $this->_getValue('lastlogin');
    }

    public function isActiveDuringLast99Days(): bool
    {
        return $this->getLastLogin() >= getCurrentDateTimeMinusDaysInMySQL(99);
    }
}

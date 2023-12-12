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

/** class for a context
 * this class implements a context item.
 */
class cs_portal_item extends cs_guide_item
{
    public $_community_list = null;

    public $_project_list = null;

    public $_room_list = null;

    public $_room_list_continuous = null;

    public $_cache_auth_source_list = null;
    private ?\cs_list $_room_list_continuous_nlct = null;
    private $_grouproom_list_count = null;

    /** constructor: cs_server_item
     * the only available constructor, initial values for internal variables.
     *
     * @param object environment the environment of the commsy
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_type = CS_PORTAL_TYPE;
        $this->_default_rubrics_array[0] = CS_COMMUNITY_TYPE;
        $this->_default_rubrics_array[1] = CS_PROJECT_TYPE;
        $this->defaultHomeConf[CS_COMMUNITY_TYPE] = 'show';
        $this->defaultHomeConf[CS_PROJECT_TYPE] = 'show';
    }

    public function isPortal(): bool
    {
        return true;
    }

    /** get max activity points of rooms.
     *
     * @return int max activity points of rooms
     */
    public function getMaxRoomActivityPoints()
    {
        $retour = 0;
        if ($this->_issetExtra('MAX_ROOM_ACTIVITY')) {
            $retour = $this->_getExtra('MAX_ROOM_ACTIVITY');
        }

        return $retour;
    }

    /** set max activity points of rooms.
     *
     * @param int max activity points of rooms
     */
    public function setMaxRoomActivityPoints($value)
    {
        $this->_addExtra('MAX_ROOM_ACTIVITY', (int) $value);
    }

    public function saveMaxRoomActivityPoints($value)
    {
        $current_value = $this->getMaxRoomActivityPoints();
        if ($current_value < $value) {
            $this->setMaxRoomActivityPoints($value);
            $this->saveWithoutChangingModificationInformation();
        }
    }

    /** get filename of picture.
     *
     * @return string filename of picture
     */
    public function getPictureFilename()
    {
        $retour = '';
        if ($this->_issetExtra('PICTUREFILENAME')) {
            $retour = $this->_getExtra('PICTUREFILENAME');
        }

        return $retour;
    }

    /** set filename of picture.
     *
     * @param string filename of picture
     */
    public function setPictureFilename($value)
    {
        $this->_addExtra('PICTUREFILENAME', (string) $value);
    }

     /** get project room link status.
      *
      * @return room link status status "optional" = project rooms can be opened without a link to a community room, "mandatory" = link is needed
      */
     public function getProjectRoomLinkStatus()
     {
         $retour = 'optional';
         if ($this->_issetExtra('PROJECTROOMLINKSTATUS')) {
             $retour = $this->_getExtra('PROJECTROOMLINKSTATUS');
         }

         return $retour;
     }

     /** set project room link status.
      *
      * @param array value room link status
      */
     public function setProjectRoomLinkStatus($value)
     {
         $this->_addExtra('PROJECTROOMLINKSTATUS', $value);
     }

     /** get community room creation status.
      *
      * @return room creation status status "all"= all users, "moderator "= only portal moderators
      */
     public function getCommunityRoomCreationStatus()
     {
         $retour = 'all';
         if ($this->_issetExtra('COMMUNITYROOMCREATIONSTATUS')) {
             $retour = $this->_getExtra('COMMUNITYROOMCREATIONSTATUS');
         }

         return $retour;
     }

     /** set community room creation status.
      *
      * @param array value room creation status
      */
     public function setCommunityRoomCreationStatus($value)
     {
         $this->_addExtra('COMMUNITYROOMCREATIONSTATUS', $value);
     }

     public function openCommunityRoomOnlyByModeration()
     {
         $retour = false;
         $status = $this->getCommunityRoomCreationStatus();
         if ('moderator' == $status) {
             $retour = true;
         }

         return $retour;
     }

     /** get project room creation status.
      *
      * @return room creation status status "portal"= on portal, too, "communityroom"= only in communityrooms
      */
     public function getProjectRoomCreationStatus()
     {
         $retour = 'portal';
         if ($this->_issetExtra('PROJECTCREATIONSTATUS')) {
             $retour = $this->_getExtra('PROJECTCREATIONSTATUS');
         }

         return $retour;
     }

     /** set project room creation status.
      *
      * @param array value room creation status
      */
     public function setProjectRoomCreationStatus($value)
     {
         $this->_addExtra('PROJECTCREATIONSTATUS', $value);
     }

     public function openProjectRoomOnlyInCommunityRoom()
     {
         $retour = false;
         $status = $this->getProjectRoomCreationStatus();
         if ('communityroom' == $status) {
             $retour = true;
         }

         return $retour;
     }

    /** set authentication connection information
     * this method sets the authentication connection information of the CommSy.
     *
     * @param string value authentication connection information
     */
    public function setAuthInfo($value)
    {
        $this->_addExtra('AUTHINFO', (array) $value);
    }

    public function getShowRoomsOnHome()
    {
        $retour = 'normal';
        if ($this->_issetExtra('SHOWROOMSONHOME')) {
            $retour = $this->_getExtra('SHOWROOMSONHOME');
        }

        return $retour;
    }

    public function setShowRoomsOnHome($value)
    {
        $this->_addExtra('SHOWROOMSONHOME', $value);
    }

    public function getNumberRoomsOnHome()
    {
        $retour = 10;
        if ($this->_issetExtra('NUMBERROOMSONHOME')) {
            $retour = $this->_getExtra('NUMBERROOMSONHOME');
        }

        return $retour;
    }

    public function setNumberRoomsOnHome($value)
    {
        $this->_addExtra('NUMBERROOMSONHOME', $value);
    }

    /** get community list
     * this function returns a list of all community rooms
     * existing at this portal.
     *
     * @return list of community rooms
     */
    public function getCommunityList()
    {
        if (!isset($this->_community_list)) {
            $manager = $this->_environment->getCommunityManager();
            $manager->setContextLimit($this->getItemID());
            $manager->select();
            $this->_community_list = $manager->get();
            unset($manager);
        }

        return $this->_community_list;
    }

    public function getProjectList()
    {
        if (!isset($this->_project_list)) {
            $manager = $this->_environment->getProjectManager();
            $manager->setContextLimit($this->getItemID());
            $manager->select();
            $this->_project_list = $manager->get();
            unset($manager);
        }

        return $this->_project_list;
    }

    public function getRoomList()
    {
        if (!isset($this->_room_list)) {
            $this->_room_list = $this->getCommunityList();
            $this->_room_list->addList($this->getProjectList());
        }

        return $this->_room_list;
    }

    public function getContinuousRoomList()
    {
        if (!isset($this->_room_list_continuous)) {
            $manager = $this->_environment->getRoomManager();
            $manager->setContextLimit($this->getItemID());
            $manager->setContinuousLimit();
            $manager->select();
            $this->_room_list_continuous = $manager->get();
            unset($manager);
        }

        return $this->_room_list_continuous;
    }

    public function getContinuousRoomListNotLinkedToTime($time_obj)
    {
        if (!isset($this->_room_list_continuous_nlct)) {
            $manager = $this->_environment->getRoomManager();
            $manager->setContextLimit($this->getItemID());
            $manager->setContinuousLimit();
            $manager->setOpenedLimit();
            $manager->select();
            $id_array1 = $manager->getIdArray();
            $manager->setTimeLimit($time_obj->getItemID());
            $manager->select();
            $id_array2 = $manager->getIdArray();
            if (is_array($id_array1) and is_array($id_array2)) {
                $id_array3 = array_diff($id_array1, $id_array2);
                if (!empty($id_array3)) {
                    $manager->resetLimits();
                    $manager->setIDArrayLimit($id_array3);
                    $manager->select();
                    $this->_room_list_continuous_nlct = $manager->get();
                }
            }
            unset($manager);
        }

        return $this->_room_list_continuous_nlct;
    }

    // ##########################################################
    // some function to get lists of items in one portal
    // ##########################################################

    public function getUsedRoomList($start, $end)
    {
        $room_manager = $this->_environment->getRoomManager();
        $room_manager->resetLimits();
        $room_manager->setContextLimit($this->getItemID());
        $room_list = $room_manager->getUsedRooms($start, $end);
        unset($room_manager);

        return $room_list;
    }

    public function getActiveRoomList($start, $end)
    {
        $room_manager = $this->_environment->getRoomManager();
        $room_manager->resetLimits();
        $room_manager->setContextLimit($this->getItemID());
        $room_list = $room_manager->getActiveRooms($start, $end);
        unset($room_manager);

        return $room_list;
    }

    public function getCountMembers()
    {
        if (!isset($this->_member_count)) {
            $manager = $this->_environment->getUserManager();
            $manager->setContextLimit($this->getItemID());
            $this->_member_count = $manager->getCountAll();
            unset($manager);
        }

        return $this->_member_count;
    }

    // ##################################################
    // time text translation methods
    // ##################################################

    public function getTimeTextArray()
    {
        $retour = [];
        if ($this->_issetExtra('TIME_TEXT_ARRAY')) {
            $retour = $this->_getExtra('TIME_TEXT_ARRAY');
        }

        return $retour;
    }

    public function setTimeTextArray($value)
    {
        $this->_addExtra('TIME_TEXT_ARRAY', $value);
    }

    public function getTimeNameArray()
    {
        $retour = [];
        if ($this->_issetExtra('TIME_NAME_ARRAY')) {
            $retour = $this->_getExtra('TIME_NAME_ARRAY');
        }

        return $retour;
    }

    public function setTimeNameArray($value)
    {
        $this->_addExtra('TIME_NAME_ARRAY', $value);

        $value2 = [];
        $value2['NAME'] = CS_TIME_TYPE;

        foreach ($value as $lang => $name) {
            $value2[mb_strtoupper((string) $lang, 'UTF-8')]['NOMPL'] = $name;
        }
        $this->setRubricArray(CS_TIME_TYPE, $value2);
    }

    /** return the current display string for time intervals as specified in
     * the current portal configuration for the currently selected language.
     */
    public function getCurrentTimeName()
    {
        $timeNamesByLanguage = $this->getTimeNameArray();
        $lang = strtoupper($this->_environment->getSelectedLanguage());

        $timeName = '';
        if ($timeNamesByLanguage && !empty($timeNamesByLanguage)) {
            if (isset($timeNamesByLanguage[$lang])) {
                $timeName = $timeNamesByLanguage[$lang];
            }
        }

        return $timeName;
    }

    public function _getShowTime()
    {
        $retour = '';
        if ($this->_issetExtra('TIME_SHOW')) {
            $retour = $this->_getExtra('TIME_SHOW');
        }

        return $retour;
    }

    public function showTime()
    {
        $retour = false;
        $value = $this->_getShowTime();
        if (1 == $value) {
            $retour = true;
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

    public function getTimeInFuture()
    {
        $retour = 0;
        if ($this->_issetExtra('TIME_IN_FUTURE')) {
            $retour = $this->_getExtra('TIME_IN_FUTURE');
        }

        return $retour;
    }

    public function setTimeInFuture($value)
    {
        $this->_addExtra('TIME_IN_FUTURE', $value);
    }

    public function getTimeList()
    {
        $retour = null;
        $time_manager = $this->_environment->getTimeManager();
        $time_manager->setContextLimit($this->getItemID());
        $time_manager->setSortOrder('title');
        $time_manager->select();
        $retour = $time_manager->get();
        unset($time_manager);

        return $retour;
    }

    public function getTimeListRev()
    {
        $retour = null;
        $time_manager = $this->_environment->getTimeManager();
        $time_manager->setContextLimit($this->getItemID());
        $time_manager->setSortOrder('title_rev');
        $time_manager->select();
        $retour = $time_manager->get();
        unset($time_manager);

        return $retour;
    }

    public function getTitleOfCurrentTime()
    {
        $retour = '';
        $current_year = date('Y');
        $year = $current_year - 1;
        $current_date = getCurrentDate();
        $clock_pulse_array = $this->getTimeTextArray();
        $found = false;
        while (!$found and $year < $current_year + 1) {
            foreach ($clock_pulse_array as $key => $clock_pulse) {
                if (isset($clock_pulse['BEGIN'][3])
                     and isset($clock_pulse['BEGIN'][4])
                ) {
                    $begin_month = $clock_pulse['BEGIN'][3].$clock_pulse['BEGIN'][4];
                } else {
                    $begin_month = '';
                }
                if (isset($clock_pulse['BEGIN'][0])
                     and isset($clock_pulse['BEGIN'][1])
                ) {
                    $begin_day = $clock_pulse['BEGIN'][0].$clock_pulse['BEGIN'][1];
                } else {
                    $begin_day = '';
                }
                if (isset($clock_pulse['END'][3])
                     and isset($clock_pulse['END'][4])
                ) {
                    $end_month = $clock_pulse['END'][3].$clock_pulse['END'][4];
                } else {
                    $end_month = '';
                }
                if (isset($clock_pulse['END'][0])
                     and isset($clock_pulse['END'][1])
                ) {
                    $end_day = $clock_pulse['END'][0].$clock_pulse['END'][1];
                } else {
                    $end_day = '';
                }
                $begin = $begin_month.$begin_day;
                $end = $end_month.$end_day;
                if ($begin > $end) {
                    $begin = $year.$begin;
                    $end = ($year + 1).$end;
                } else {
                    $begin = $year.$begin;
                    $end = $year.$end;
                }
                if ($begin <= $current_date
                     and $current_date <= $end
                ) {
                    $found = true;
                    $retour = $year.'_'.$key;
                }
            }
            ++$year;
        }

        return $retour;
    }

    public function getCurrentTimeItem()
    {
        $retour = null;
        $time_manager = $this->_environment->getTimeManager();
        $time_manager->setContextLimit($this->getItemID());
        $time_manager->setTypeLimit('time');
        $retour = $time_manager->getItemByName($this->getTitleOfCurrentTime());
        unset($time_manager);

        return $retour;
    }

    public function save()
    {
        $item_id = $this->getItemID();
        parent::save();
        $this->_time_list = null;
    }

    /** delete portal
     * this method portal the community.
     */
    public function delete()
    {
        parent::delete();

        $manager = $this->_environment->getPortalManager();
        $this->_delete($manager);
        unset($manager);
    }

    // #########################################################
    // statistic functions
    // #########################################################

    public function getCountUsedAccounts($start, $end)
    {
        $retour = 0;

        $user_manager = $this->_environment->getUserManager();
        $user_manager->resetLimits();
        $user_manager->setContextLimit($this->getItemID());
        $retour = $user_manager->getCountUsedAccounts($start, $end);
        unset($user_manager);

        return $retour;
    }

    public function getCountOpenAccounts($start, $end)
    {
        $retour = 0;

        $user_manager = $this->_environment->getUserManager();
        $user_manager->resetLimits();
        $user_manager->setContextLimit($this->getItemID());
        $retour = $user_manager->getCountOpenAccounts($start, $end);
        unset($user_manager);

        return $retour;
    }

    public function getCountAllAccounts($start, $end)
    {
        $retour = 0;

        $user_manager = $this->_environment->getUserManager();
        $user_manager->resetLimits();
        $user_manager->setContextLimit($this->getItemID());
        $retour = $user_manager->getCountAllAccounts($start, $end);
        unset($user_manager);

        return $retour;
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

    public function getCountAllTypeRooms($type, $start, $end)
    {
        $retour = 0;

        $room_manager = $this->_environment->getRoomManager();
        $room_manager->resetLimits();
        $room_manager->setContextLimit($this->getItemID());
        $retour = $room_manager->getCountAllTypeRooms($type, $start, $end);
        unset($room_manager);

        return $retour;
    }

    public function getCountUsedTypeRooms($type, $start, $end)
    {
        $retour = 0;

        $room_manager = $this->_environment->getRoomManager();
        $room_manager->resetLimits();
        $room_manager->setContextLimit($this->getItemID());
        $retour = $room_manager->getCountUsedTypeRooms($type, $start, $end);
        unset($room_manager);

        return $retour;
    }

    public function getCountActiveTypeRooms($type, $start, $end)
    {
        $retour = 0;

        $room_manager = $this->_environment->getRoomManager();
        $room_manager->resetLimits();
        $room_manager->setContextLimit($this->getItemID());
        $retour = $room_manager->getCountActiveTypeRooms($type, $start, $end);
        unset($room_manager);

        return $retour;
    }

    /** get UsageInfos
     * this method returns the usage infos.
     *
     * @return array
     */
    public function getUsageInfoArray()
    {
        $retour = null;
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

        return $retour;
    }

    /** set UsageInfos
     * this method sets the usage infos.
     *
     * @param array
     */
    public function setUsageInfoArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO', $value_array);
        }
    }

    /** set UsageInfos
     * this method sets the usage infos.
     *
     * @param array
     */
    public function setUsageInfoFormArray($value_array)
    {
        if (is_array($value_array)) {
            $this->_addExtra('USAGE_INFO_FORM', $value_array);
        }
    }

    /** get UsageInfos
     * this method returns the usage infos.
     *
     * @return array
     */
    public function getUsageInfoFormArray()
    {
        $retour = null;
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

        return $retour;
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
        if (isset($retour[mb_strtoupper((string) $rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper((string) $rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper((string) $rubric, 'UTF-8')];
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
        $value_array[mb_strtoupper((string) $rubric, 'UTF-8')] = $string;
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
        if (isset($retour[mb_strtoupper((string) $rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper((string) $rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper((string) $rubric, 'UTF-8')];
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
        $value_array[mb_strtoupper((string) $rubric, 'UTF-8')] = $string;
        $this->_addExtra('USAGE_INFO_FORM_HEADER', $value_array);
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
        $value_array[mb_strtoupper((string) $rubric, 'UTF-8')] = $string;
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
        if (isset($retour[mb_strtoupper((string) $rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper((string) $rubric, 'UTF-8')])) {
            $retour = $retour[mb_strtoupper((string) $rubric, 'UTF-8')];
        } else {
            $translator = $this->_environment->getTranslationObject();
            $mod = $this->_environment->getCurrentModule();
            $fct = $this->_environment->getCurrentFunction();
            if ('configuration' == $mod and 'time' == $fct) { // no link in message tag
                $retour = $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_TIME_FORM');
                $temp = 'CONFIGURATION_TIME';
            } else {
                $temp = mb_strtoupper((string) $rubric, 'UTF-8').'_'.mb_strtoupper($funct, 'UTF-8');
                $tempMessage = '';
                $tempMessage = match ($temp) {
                    'ACCOUNT_ACTION' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_ACCOUNT_ACTION_FORM'),
                    'ACCOUNT_EDIT' => $translator->getMessage('USAGE_INFO_FORM_COMING_SOON'),
                    'ACCOUNT_STATUS' => $translator->getMessage('USAGE_INFO_FORM_COMING_SOON'),
                    'COMMUNITY_EDIT' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_COMMUNITY_EDIT_FORM'),
                    'CONFIGURATION_AGB' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_AGB_FORM'),
                    'CONFIGURATION_AUTHENTICATION' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_AUTHENTICATION_FORM'),
                    'CONFIGURATION_COMMON' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_COMMON_FORM'),
                    'CONFIGURATION_DEFAULTS' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_DEFAULTS_FORM'),
                    'CONFIGURATION_EXPORT' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_EXPORT_FORM'),
                    'CONFIGURATION_MAIL' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_MAIL_FORM'),
                    'CONFIGURATION_MOVE' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_MOVE_FORM'),
                    'CONFIGURATION_NEWS' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_NEWS_FORM'),
                    'CONFIGURATION_PORTALHOME' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_PORTALHOME_FORM'),
                    'CONFIGURATION_PORTALUPLOAD' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_PORTALUPLOAD_FORM'),
                    'CONFIGURATION_PREFERENCES' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_PREFERENCES_FORM'),
                    'CONFIGURATION_ROOM_OPENING' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_ROOM_OPENING_FORM'),
                    'CONFIGURATION_SERVICE' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_SERVICE_FORM'),
                    'CONFIGURATION_WIKI' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_WIKI_FORM'),
                    'CONFIGURATION_AUTOACCOUNTS' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_AUTOACCOUNTS_FORM'),
                    'PROJECT_EDIT' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_PROJECT_EDIT_FORM'),
                    'MAIL_TO_MODERATOR' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_MAIL_TO_MODERATOR_FORM'),
                    'MAIL_PROCESS' => $translator->getMessage('USAGE_INFO_FORM_COMING_SOON'),
                    'LANGUAGE_UNUSED' => $translator->getMessage('USAGE_INFO_TEXT_LANGUAGE_UNUSED_FORM'),
                    'CONFIGURATION_PLUGIN' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_PLUGIN_FORM'),
                    'ACCOUNT_PASSWORD' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_ACCOUNT_PASSWORD_FORM'),
                    'CONFIGURATION_HTMLTEXTAREA' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_HTMLTEXTAREA_FORM'),
                    'CONFIGURATION_PLUGINS' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_PLUGINS_FORM'),
                    'CONFIGURATION_LANGUAGE' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_LANGUAGE_FORM'),
                    'CONFIGURATION_DATASECURITY' => $translator->getMessage('USAGE_INFO_COMING_SOON'),
                    'CONFIGURATION_INACTIVE' => $translator->getMessage('USAGE_INFO_COMING_SOON'),
                    'CONFIGURATION_INACTIVEPROCESS' => $translator->getMessage('USAGE_INFO_COMING_SOON'),
                    'CONFIGURATION_EXPORT_IMPORT' => $translator->getMessage('USAGE_INFO_TEXT_PORTAL_FOR_CONFIGURATION_EXPORT_IMPORT_FORM'),
                    default => $translator->getMessage('COMMON_MESSAGETAG_ERROR').' cs_portal_item('.__LINE__.')',
                };
                $retour = $tempMessage;
            }
            if ($retour == 'USAGE_INFO_TEXT_PORTAL_FOR_'.$temp.'_FORM' or 'tbd' == $retour) {
                $retour = $translator->getMessage('USAGE_INFO_FORM_COMING_SOON');
            }
        }

        return $retour;
    }

    // ###############################################################
    // Authentication
    // ###############################################################

    public function setAuthDefault($value)
    {
        $this->_addExtra('DEFAULT_AUTH', $value);
    }

    public function setAuthIMS($value)
    {
        $this->_addExtra('IMS_AUTH', $value);
    }

    public function getAuthDefault()
    {
        $retour = '';
        if ($this->_issetExtra('DEFAULT_AUTH')) {
            $value = $this->_getExtra('DEFAULT_AUTH');
            if (!empty($value)) {
                $retour = $value;
            }
        }

        return $retour;
    }

    public function getAuthIMS()
    {
        $retour = '';
        if ($this->_issetExtra('IMS_AUTH')) {
            $value = $this->_getExtra('IMS_AUTH');
            if (!empty($value)) {
                $retour = $value;
            }
        }

        return $retour;
    }

    public function getDefaultAuthSourceItem()
    {
        $retour = null;
        $default_auth_item_id = $this->getAuthDefault();
        if (!empty($default_auth_item_id)) {
            $manager = $this->_environment->getAuthSourceManager();
            $item = $manager->getItem($default_auth_item_id);
            if (isset($item)) {
                $retour = $item;
            }
            unset($item);
        }

        return $retour;
    }

    public function getAuthSourceList()
    {
        $retour = null;
        if (!isset($this->_cache_auth_source_list)) {
            $manager = $this->_environment->getAuthSourceManager();
            $manager->setContextLimit($this->getItemID());
            $manager->select();
            $retour = $manager->get();
            if ($this->_cache_on) {
                $this->_cache_auth_source_list = $retour;
            }
        } else {
            $retour = $this->_cache_auth_source_list;
        }

        return $retour;
    }

    public function getAuthSourceListEnabled()
    {
        $list = $this->getAuthSourceList();
        if (!$list->isEmpty()) {
            $item = $list->getFirst();
            while ($item) {
                if (!$item->show()) {
                    $list->removeElement($item);
                }
                $item = $list->getNext();
            }
        }

        return $list;
    }

    public function getAuthSourceListCASEnabled()
    {
        $list = $this->getAuthSourceList();
        if (!$list->isEmpty()) {
            $item = $list->getFirst();
            while ($item) {
                if (!$item->show() or 'CAS' != mb_strtoupper((string) $item->getSourceType(), 'UTF-8')) {
                    $list->removeElement($item);
                }
                $item = $list->getNext();
            }
        }

        return $list;
    }

    public function getAuthSourceListTypo3WebEnabled()
    {
        $list = $this->getAuthSourceList();
        if (!$list->isEmpty()) {
            $item = $list->getFirst();
            while ($item) {
                if (!$item->show() or 'TYPO3WEB' != mb_strtoupper((string) $item->getSourceType(), 'UTF-8')) {
                    $list->removeElement($item);
                }
                $item = $list->getNext();
            }
        }

        return $list;
    }

    public function getAuthSource($item_id)
    {
        $manager = $this->_environment->getAuthSourceManager();

        return $manager->getItem($item_id);
    }

    public function getCountAuthSourceListEnabled()
    {
        $retour = 0;
        $list = $this->getAuthSourceListEnabled();
        if (isset($list)) {
            $retour = $list->getcount();
        }

        return $retour;
    }

    public function setShowAuthAtLogin()
    {
        $this->_addExtra('AUTH_SHOW_LOGIN', 1);
    }

    public function setNotShowAuthAtLogin()
    {
        $this->_addExtra('AUTH_SHOW_LOGIN', -1);
    }

    private function _getShowAuthAtLogin()
    {
        $retour = '';
        if ($this->_issetExtra('AUTH_SHOW_LOGIN')) {
            $value = $this->_getExtra('AUTH_SHOW_LOGIN');
            if (!empty($value)) {
                $retour = $value;
            }
        }

        return $retour;
    }

    public function showAuthAtLogin()
    {
        $retour = true;
        $show = $this->_getShowAuthAtLogin();
        if (!empty($show)
             and -1 == $show
        ) {
            $retour = false;
        }

        return $retour;
    }

    // ##########################################
    // portal description wellcome text
    // ##########################################

    /** get description array.
     *
     * @return array description text in different languages
     */
    public function getDescriptionWellcome1Array()
    {
        $retour = [];
        if ($this->_issetExtra('DESCRIPTION_WELLCOME_1')) {
            $retour = $this->_getExtra('DESCRIPTION_WELLCOME_1');
        }

        return $retour;
    }

    /** set description array.
     *
     * @param array value description text in different languages
     */
    public function setDescriptionWellcome1Array($value)
    {
        $this->_addExtra('DESCRIPTION_WELLCOME_1', (array) $value);
    }

    /** get description of a context
     * this method returns the description of the context.
     *
     * @return string description of a context
     */
    public function getDescriptionWellcome1ByLanguage($language)
    {
        $retour = null;
        if ('browser' == $language) {
            $language = $this->_environment->getSelectedLanguage();
        }
        $desc_array = $this->getDescriptionWellcome1Array();
        if (isset($desc_array[cs_strtoupper($language)])) {
            $retour = $desc_array[cs_strtoupper($language)];
        } else {
            $translator = $this->_environment->getTranslationObject();
            $retour = $translator->getMessageInLang(mb_strtolower((string) $language, 'UTF-8'), 'HOMEPAGE_PAGE_ROOT_TITLE').' '.$translator->getMessageInLang(mb_strtolower((string) $language, 'UTF-8'), 'COMMON_IN').' ...';
        }

        return $retour;
    }

    public function getDescriptionWellcome1()
    {
        $retour = '';
        $retour = $this->getDescriptionWellcome1ByLanguage($this->_environment->getSelectedLanguage());
        if (!isset($retour)) {
            $current_user = $this->_environment->getCurrentUserItem();
            $retour = $this->getDescriptionWellcome1ByLanguage($this->_environment->getUserLanguage());
        }
        if (!isset($retour)) {
            $translator = $this->_environment->getTranslationObject();
            $retour = $translator->getMessage('HOMEPAGE_PAGE_ROOT_TITLE').' '.$translator->getMessage('COMMON_IN').' ...';
        }

        return $retour;
    }

    /** set description of a context
     * this method sets the description of the context.
     *
     * @param string value description of the context
     * @param string value lanugage of the description
     */
    public function setDescriptionWellcome1ByLanguage($value, $language)
    {
        $desc_array = $this->getDescriptionWellcome1Array();
        $desc_array[mb_strtoupper((string) $language, 'UTF-8')] = $value;
        $this->setDescriptionWellcome1Array($desc_array);
    }

    /** get description array.
     *
     * @return array description text in different languages
     */
    public function getDescriptionWellcome2Array()
    {
        $retour = [];
        if ($this->_issetExtra('DESCRIPTION_WELLCOME_2')) {
            $retour = $this->_getExtra('DESCRIPTION_WELLCOME_2');
        }

        return $retour;
    }

    /** set description array.
     *
     * @param array value description text in different languages
     */
    public function setDescriptionWellcome2Array($value)
    {
        $this->_addExtra('DESCRIPTION_WELLCOME_2', (array) $value);
    }

    /** get description of a context
     * this method returns the description of the context.
     *
     * @return string description of a context
     */
    public function getDescriptionWellcome2ByLanguage($language)
    {
        $retour = null;
        if ('browser' == $language) {
            $language = $this->_environment->getSelectedLanguage();
        }
        $desc_array = $this->getDescriptionWellcome2Array();
        if (isset($desc_array[cs_strtoupper($language)])) {
            $retour = $desc_array[cs_strtoupper($language)];
        } else {
            $retour = '... '.$this->getTitle();
        }

        return $retour;
    }

    public function getDescriptionWellcome2()
    {
        $retour = '';
        $retour = $this->getDescriptionWellcome2ByLanguage($this->_environment->getSelectedLanguage());
        if (!isset($retour)) {
            $current_user = $this->_environment->getCurrentUserItem();
            $retour = $this->getDescriptionWellcome2ByLanguage($this->_environment->getUserLanguage());
        }
        if (!isset($retour)) {
            $retour = '... '.$this->getTitle();
        }

        return $retour;
    }

    /** set description of a context
     * this method sets the description of the context.
     *
     * @param string value description of the context
     * @param string value lanugage of the description
     */
    public function setDescriptionWellcome2ByLanguage($value, $language)
    {
        $desc_array = $this->getDescriptionWellcome2Array();
        $desc_array[mb_strtoupper((string) $language, 'UTF-8')] = $value;
        $this->setDescriptionWellcome2Array($desc_array);
    }

    public function showAllwaysPrivateRoomLink()
    {
        $retour = true;
        $value = $this->_getShowPrivateRoomLink();
        if (-1 == $value) {
            $retour = false;
        }

        return $retour;
    }

    private function _getShowPrivateRoomLink()
    {
        $retour = 1;
        if ($this->_issetExtra('SHOW_PRIVATE_ROOM_LINK')) {
            $retour = $this->_getExtra('SHOW_PRIVATE_ROOM_LINK');
        }

        return $retour;
    }

    private function _setShowPrivateRoomLink($value)
    {
        $this->_setExtra('SHOW_PRIVATE_ROOM_LINK', (int) $value);
    }

    public function setShowAllwaysPrivateRoomLink()
    {
        $this->_setShowPrivateRoomLink(1);
    }

    public function unsetShowAllwaysPrivateRoomLink()
    {
        $this->_setShowPrivateRoomLink(-1);
    }

    // ###########################################
    // count rooms
    // ###########################################

    /** get count project rooms in extras.
     *
     * @return int count project rooms
     */
    private function _getCountProjectRoomsExtra()
    {
        $retour = 0;
        if ($this->_issetExtra('COUNT_ROOM_PROJECT')) {
            $retour = (int) $this->_getExtra('COUNT_ROOM_PROJECT');
        }

        return $retour;
    }

    /** set count project rooms in extras.
     *
     * @param int count project rooms
     */
    private function _setCountProjectRoomsExtra($value)
    {
        $this->_addExtra('COUNT_ROOM_PROJECT', (int) $value);
    }

    /** increase count project rooms in extras.
     *
     * @param bool save portal item? default = false
     */
    public function increaseCountProjectRoomsExtra($save = false)
    {
        $this->_setCountProjectRoomsExtra((int) ($this->_getCountProjectRoomsExtra() + 1));
        if ($save) {
            $this->save();
        }
    }

    /** decrease count project rooms in extras.
     *
     * @param bool save portal item? default = false
     */
    public function decreaseCountProjectRoomsExtra($save = false)
    {
        $this->_setCountProjectRoomsExtra((int) ($this->_getCountProjectRoomsExtra() - 1));
        if ($save) {
            $this->save();
        }
    }

    /** get count community rooms in extras.
     *
     * @return int count community rooms
     */
    private function _getCountCommunityRoomsExtra()
    {
        $retour = 0;
        if ($this->_issetExtra('COUNT_ROOM_COMMUNITY')) {
            $retour = (int) $this->_getExtra('COUNT_ROOM_COMMUNITY');
        }

        return $retour;
    }

    /** set count community rooms in extras.
     *
     * @param int count community rooms
     */
    private function _setCountCommunityRoomsExtra($value)
    {
        $this->_addExtra('COUNT_ROOM_COMMUNITY', (int) $value);
    }

    /** increase count community rooms in extras.
     *
     * @param bool save portal item? default = false
     */
    public function increaseCountCommunityRoomsExtra($save = false)
    {
        $this->_setCountCommunityRoomsExtra((int) ($this->_getCountCommunityRoomsExtra() + 1));
        if ($save) {
            $this->save();
        }
    }

    /** decrease count community rooms in extras.
     *
     * @param bool save portal item? default = false
     */
    public function decreaseCountCommunityRoomsExtra($save = false)
    {
        $this->_setCountCommunityRoomsExtra((int) ($this->_getCountCommunityRoomsExtra() - 1));
        if ($save) {
            $this->save();
        }
    }

    /** get count group rooms in extras.
     *
     * @return int count group rooms
     */
    private function _getCountGroupRoomsExtra()
    {
        $retour = 0;
        if ($this->_issetExtra('COUNT_ROOM_GROUP')) {
            $retour = (int) $this->_getExtra('COUNT_ROOM_GROUP');
        }

        return $retour;
    }

    /** set count group rooms in extras.
     *
     * @param int count group rooms
     */
    private function _setCountGroupRoomsExtra($value)
    {
        $this->_addExtra('COUNT_ROOM_GROUP', (int) $value);
    }

    /** increase count group rooms in extras.
     *
     * @param bool save portal item? default = false
     */
    public function increaseCountGroupRoomsExtra($save = false)
    {
        $this->_setCountGroupRoomsExtra((int) ($this->_getCountGroupRoomsExtra() + 1));
        if ($save) {
            $this->save();
        }
    }

    /** decrease count group rooms in extras.
     *
     * @param bool save portal item? default = false
     */
    public function decreaseCountGroupRoomsExtra($save = false)
    {
        $this->_setCountGroupRoomsExtra((int) ($this->_getCountGroupRoomsExtra() - 1));
        if ($save) {
            $this->save();
        }
    }

    /** get count private rooms in extras.
     *
     * @return int count private rooms
     */
    private function _getCountPrivateRoomsExtra()
    {
        $retour = 0;
        if ($this->_issetExtra('COUNT_ROOM_PRIVATE')) {
            $retour = (int) $this->_getExtra('COUNT_ROOM_PRIVATE');
        }

        return $retour;
    }

    /** set count private rooms.
     *
     * @param int count private rooms
     */
    private function _setCountPrivateRoomsExtra($value)
    {
        $this->_addExtra('COUNT_ROOM_PRIVATE', (int) $value);
    }

    /** increase count private rooms in extras.
     *
     * @param bool save portal item? default = false
     */
    public function increaseCountPrivateRoomsExtra($save = false)
    {
        $this->_setCountPrivateRoomsExtra((int) ($this->_getCountPrivateRoomsExtra() + 1));
        if ($save) {
            $this->save();
        }
    }

    /** decrease count private rooms in extras.
     *
     * @param bool save portal item? default = false
     */
    public function decreaseCountPrivateRoomsExtra($save = false)
    {
        $this->_setCountPrivateRoomsExtra((int) ($this->_getCountPrivateRoomsExtra() - 1));
        if ($save) {
            $this->save();
        }
    }

    /** get count project rooms from manager.
     *
     * @return int count project rooms
     */
    private function _getCountProjectRoomsManager()
    {
        if (!isset($this->_project_list_count)) {
            $manager = $this->_environment->getProjectManager();
            $manager->setContextLimit($this->getItemID());
            $this->_project_list_count = $manager->getCountAll();
            unset($manager);
        }

        return $this->_project_list_count;
    }

    /** get count community rooms from manager.
     *
     * @return int count community rooms
     */
    private function _getCountCommunityRoomsManager()
    {
        if (!isset($this->_community_list_count)) {
            $manager = $this->_environment->getCommunityManager();
            $manager->setContextLimit($this->getItemID());
            $this->_community_list_count = $manager->getCountAll();
            unset($manager);
        }

        return $this->_community_list_count;
    }

    /** get count private rooms from manager.
     *
     * @return int count private rooms
     */
    private function _getCountPrivateRoomsManager()
    {
        if (!isset($this->_private_list_count)) {
            $manager = $this->_environment->getPrivateRoomManager();
            $manager->setContextLimit($this->getItemID());
            $this->_private_list_count = $manager->getCountAll();
            unset($manager);
        }

        return $this->_private_list_count;
    }

    private function _getCountRoomRedundancy()
    {
        $retour = -1;
        if ($this->_issetExtra('COUNT_ROOM_REDUNDANCY')) {
            $value = (int) $this->_getExtra('COUNT_ROOM_REDUNDANCY');
        }

        return $retour;
    }

    private function _setCountRoomRedundancy($value)
    {
        $this->_addExtra('COUNT_ROOM_REDUNDANCY', (int) $value);
    }

    // Datenschutz
    public function getLockTime()
    {
        $retour = 0;
        if ($this->_issetExtra('LOCK_TIME')) {
            $retour = $this->_getExtra('LOCK_TIME');
        }

        return $retour;
    }

    public function setLockTime($time)
    {
        $this->_addExtra('LOCK_TIME', $time);
    }

    public function getPasswordGeneration()
    {
        $retour = 0;
        if ($this->_issetExtra('PASSWORD_GENERATION')) {
            $retour = $this->_getExtra('PASSWORD_GENERATION');
        }

        return $retour;
    }

    public function setLockTimeInterval($seconds)
    {
        $this->_addExtra('LOCK_INTERVAL', $seconds);
    }

    public function getLockTimeInterval()
    {
        $retour = 0;
        if ($this->_issetExtra('LOCK_INTERVAL')) {
            $retour = $this->_getExtra('LOCK_INTERVAL');
        }

        return $retour;
    }

    public function setTryUntilLock($number)
    {
        $this->_addExtra('TRY_UNTIL_LOCK', $number);
    }

    public function getTryUntilLock()
    {
        $retour = 0;
        if ($this->_issetExtra('TRY_UNTIL_LOCK')) {
            $retour = $this->_getExtra('TRY_UNTIL_LOCK');
        }

        return $retour;
    }

    public function isPasswordGenerationActive()
    {
        $retour = false;
        if ($this->_issetExtra('PASSWORD_GENERATION')) {
            if ($this->getPasswordGeneration() > 0) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setPasswordGeneration($value)
    {
        $this->_addExtra('PASSWORD_GENERATION', $value);
    }

    public function isPasswordExpirationActive()
    {
        $retour = false;
        if ($this->_issetExtra('PASSWORD_EXPIRATION')) {
            if ($this->getPasswordExpiration() > 0) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function getPasswordExpiration()
    {
        $retour = 0;
        if ($this->_issetExtra('PASSWORD_EXPIRATION')) {
            $retour = $this->_getExtra('PASSWORD_EXPIRATION');
        }

        return $retour;
    }

    public function setPasswordExpiration($value)
    {
        $this->_addExtra('PASSWORD_EXPIRATION', $value);
    }

    public function setDaysBeforeExpiringPasswordSendMail($days)
    {
        $this->_addExtra('DAYSBEFORE_EXPIRINGPW_SENDMAIL', $days);
    }

    public function getDaysBeforeExpiringPasswordSendMail()
    {
        $retour = 0;
        if ($this->_issetExtra('DAYSBEFORE_EXPIRINGPW_SENDMAIL')) {
            $retour = $this->_getExtra('DAYSBEFORE_EXPIRINGPW_SENDMAIL');
        }

        return $retour;
    }

    // Datenschutz
    public function setTemporaryLock($value)
    {
        $this->_addExtra('TEMPORARY_LOCK', $value);
    }

    public function getTemporaryLock()
    {
        $retour = '';
        $value = $this->_getExtra('TEMPORARY_LOCK');
        if (!empty($value)) {
            $retour = $value;
        }

        return $retour;
    }

    public function isTemporaryLockActivated()
    {
        if (1 == $this->getTemporaryLock()) {
            return true;
        } else {
            return false;
        }
    }

    /** set wordpress url.
     *
     * @param string url
     */
    public function setWordpressUrl($value)
    {
        $this->_addExtra('WP_URL', $value);
    }

    /** get wordpress url.
     *
     * @param string url
     */
    public function getWordpressUrl()
    {
        $retour = '';
        $value = $this->_getExtra('WP_URL');
        if (!empty($value)) {
            $retour = $value;
        }

        return $retour;
    }

    /** set activate wordpress blog.
     *
     * @param bool
     */
    public function setWordpressPortalActive($value)
    {
        $this->_addExtra('WP_PORTAL_ACTIVE', $value);
    }

    /** get activate wordpress blog.
     *
     * @param bool
     */
    public function getWordpressPortalActive()
    {
        $retour = false;
        $value = $this->_getExtra('WP_PORTAL_ACTIVE');
        if ($value) {
            $retour = true;
        }

        return $retour;
    }
}
